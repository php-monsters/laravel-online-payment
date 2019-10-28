<?php

namespace Tartan\Larapay;

use Illuminate\Http\Request;
use Tartan\Larapay\Adapter\AdapterInterface;
use Tartan\Larapay\Exceptions\FailedTransactionException;
use Tartan\Larapay\Models\LarapayTransaction;
use Tartan\Larapay\Transaction\TransactionInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tartan\Log\Facades\XLog;
use Exception;

class Factory
{
    /**
     * @var AdapterInterface
     */
    protected $gateway;

    /**
     * @param $adapter adapter name
     * @param TransactionInterface $invoice
     * @param array adapter configuration
     *
     * @return $this
     * @throws Exception
     */
    public function make(string $adapter, TransactionInterface $invoice, array $adapterConfig = [])
    {
        $adapter = ucfirst(strtolower($adapter));

        /**
         *  check for supported gateways
         */
        $readyToServerGateways = explode(',', config('larapay.gateways'));

        Log::debug('selected gateway [' . $adapter . ']');
        Log::debug('available gateways', $readyToServerGateways);

        if (!in_array($adapter, $readyToServerGateways)) {
            throw new Exception(trans('larapay::larapay.gate_not_ready'));
        }

        $adapterNamespace = 'Tartan\Larapay\Adapter\\';
        $adapterName = $adapterNamespace . $adapter;

        if (!class_exists($adapterName)) {
            throw new Exception("Adapter class '$adapterName' does not exist");
        }

        $config = count($adapterConfig) ? $adapterConfig : config('larapay.' . strtolower($adapter));
        Log::debug('init gateway config', $config);

        $bankAdapter = new $adapterName($invoice, $config);

        if (!$bankAdapter instanceof AdapterInterface) {
            throw new Exception(trans('larapay::larapay.gate_not_ready'));
        }

        // setting soapClient options if required
        if (config('larapay.soap.useOptions') == true) {
            $bankAdapter->setSoapOptions(config('larapay.soap.options'));
        }

        $this->gateway = $bankAdapter;

        return $this;
    }

    public function verifyTransaction(Request $request)
    {
        $gateway = $request->input('gateway');
        $transactionId = $request->input('transactionId');

        XLog::debug('request: ', $request->all());

        $referenceId = '';
        $paidTime = '';
        $amount = '';

        $validator = Validator::make([
            'transactionId' => $transactionId,
            'gateway' => $gateway,
        ], [
            'transactionId' => [
                'required',
                'numeric',
            ],
            'gateway' => [
                'required',
            ],
        ]);

        // validate required route parameters
        if ($validator->fails()) {
            throw new FailedTransactionException(__('Code N1 - Transaction not found'));
        }
        // find the transaction by token
        $transaction = LarapayTransaction::find($transactionId);
        //transaction not found in our database
        if (!$transaction) {
            throw new FailedTransactionException(__('Code N2 - Transaction not found'));
        }
        //transaction gateway conflict
        if ($transaction->gate_name != $gateway) {
            throw new FailedTransactionException(__('Code N3 - Transaction not found'));
        }


        try {
            // update transaction`s callback parameter
            $transaction->setCallBackParameters($request->all());

            $gatewayProperties = json_decode($transaction->gateway_properties, true);
            $paymentGatewayHandler = $this->make($gateway, $transaction, $gatewayProperties);

            if ($paymentGatewayHandler->canContinueWithCallbackParameters($request->all()) !== true) {
                throw new FailedTransactionException(trans('gate.could_not_continue_because_of_callback_params'));
            }

            // گرفتن Reference Number از پارامترهای دریافتی از درگاه پرداخت
            $referenceId = $paymentGatewayHandler->getGatewayReferenceId();

            // جلوگیری از double spending یک شناسه مرجع تراکنش
            $doubleInvoice = LarapayTransaction::where('gate_refid', $referenceId)
                ->where('verified', true)//قبلا وریفای شده
                ->where('gate_name', $transaction->gate_name)
                ->first();


            if (!empty($doubleInvoice)) {
                // double spending شناسایی شد
                XLog::emergency('referenceId double spending detected', [
                    'tag' => $referenceId,
                    'order_id' => $transaction->gateway_order_id,
                    'ips' => $request->ips(),
                    'gateway' => $gateway,
                ]);

                // آپدیت کردن توصیحات فاکتور
                if (!preg_match('/DOUBLE_SPENDING/i', $transaction->description)) {
                    $transaction->description = "#DOUBLE_SPENDING_BY_{$doubleInvoice->id}#\n" . $transaction->description;
                    $transaction->save();
                }

                throw new FailedTransactionException(trans('gate.double_spending'));
            }

            $transaction->setReferenceId($referenceId);

            // verify start ----------------------------------------------------------------------------------------
            $verified = false;
            // سه بار تلاش برای تایید تراکنش
            for ($i = 1; $i <= 3; $i++) {
                try {
                    XLog::info('trying to verify payment',
                        ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);

                    $verifyResult = $paymentGatewayHandler->verify();
                    if ($verifyResult) {
                        $verified = true;
                    }
                    XLog::info('verify result',
                        ['result' => $verifyResult, 'try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                    break;
                } catch (Exception $e) {
                    XLog::error('Exception: ' . $e->getMessage(),
                        ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                    continue;
                }
            }

            if ($verified !== true) {
                XLog::error('transaction verification failed', ['tag' => $referenceId, 'gateway' => $gateway]);
                throw new FailedTransactionException('transaction verification failed');
            } else {
                XLog::info('invoice verified successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
            }

            // verify end ------------------------------------------------------------------------------------------

            // after verify start ----------------------------------------------------------------------------------
            $afterVerified = false;
            for ($i = 1; $i <= 3; $i++) {
                try {
                    XLog::info('trying to after verify payment',
                        ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);

                    $afterVerifyResult = $paymentGatewayHandler->afterVerify();
                    if ($afterVerifyResult) {
                        $afterVerified = true;
                    }
                    XLog::info('after verify result', [
                        'result' => $afterVerifyResult,
                        'try' => $i,
                        'tag' => $referenceId,
                        'gateway' => $gateway,
                    ]);
                    break;
                } catch (\Exception $e) {
                    XLog::error('Exception: ' . $e->getMessage(),
                        ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                    continue;
                }
            }

            if ($afterVerified !== true) {
                XLog::error('transaction after verification failed',
                    ['tag' => $referenceId, 'gateway' => $gateway]);
                throw new FailedTransactionException('transaction after verification failed');
            } else {
                XLog::info('invoice after verified successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
            }
            // after verify end ------------------------------------------------------------------------------------

            $paidSuccessfully = true;

        } catch (Exception $e) {
            XLog::emergency($e->getMessage() . ' code:' . $e->getCode() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new FailedTransactionException($e->getMessage(), $e->getCode(), $e);
        }

        // خدمات به مشتری ارائه شد
        Log::info('invoice completed successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
        // فلگ زدن فاکتور بعنوان فاکتور موفق
        $transaction->setAccomplished(true);

        return $transaction;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (empty($this->gateway)) {
            throw new Exception("Gateway not defined before! please use make method to initialize gateway");
        }

        Log::info($name, $arguments);

        // چو ن همیشه متد ها با یک پارامتر کلی بصورت آرایه فراخوانی میشوند. مثلا:
        // $paymentGatewayHandler->generateForm($ArrayOfExtraPaymentParams)
        if (count($arguments) > 0) {
            $this->gateway->setParameters($arguments[0]); // set parameters
        }

        try {
            return call_user_func_array([$this->gateway, $name], $arguments); // call desire method
        } catch (\Exception $e) {
            Log::error($e->getMessage() . ' Code:' . $e->getCode() . ' File:' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }
}
