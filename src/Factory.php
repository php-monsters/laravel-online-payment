<?php

namespace Tartan\Larapay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tartan\Larapay\Adapter\AdapterInterface;
use Tartan\Larapay\Exceptions\FailedTransactionException;
use Tartan\Larapay\Exceptions\TransactionNotFoundException;
use Tartan\Larapay\Models\LarapayTransaction;
use Tartan\Larapay\Transaction\TransactionInterface;
use PhpMonsters\Log\Facades\XLog;
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

        XLog::debug('selected gateway [' . $adapter . ']');
        XLog::debug('available gateways', $readyToServerGateways);
        if (!in_array($adapter, $readyToServerGateways)) {
            throw new Exception(trans('larapay::larapay.gate_not_ready'));
        }

        $adapterNamespace = 'Tartan\Larapay\Adapter\\';
        $adapterName = $adapterNamespace . $adapter;

        if (!class_exists($adapterName)) {
            throw new Exception("Adapter class '$adapterName' does not exist");
        }

        $config = count($adapterConfig) ? $adapterConfig : config('larapay.' . strtolower($adapter));
        XLog::debug('init gateway config', $config);

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

    public function verifyTransaction(Request $request, array $adapterConfig = [])
    {
        //get gateway and transaction id from request
        $gateway = $request->gateway;
        $transactionId = $request->transaction_id;

        $parameters = $request->all();
        $parameters ['routes'] = $request->route()->parameters();
        //log all incoming data for debug request
        XLog::debug('request: ', $parameters);


        $referenceId = '';
        $paidTime = '';
        $amount = '';

        //validate incoming request parameters
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
            throw new FailedTransactionException(trans('larapay::larapay.invalid_response'));
        }
        // find the transaction by token
        $transaction = LarapayTransaction::find($transactionId);
        //transaction not found in our database
        if (!$transaction) {
            throw new TransactionNotFoundException(trans('larapay::larapay.transaction_not_found'), 2);
        }
        //transaction gateway conflict
        if ($transaction->gate_name != $gateway) {
            throw new TransactionNotFoundException(trans('larapay::larapay.transaction_not_found'), 3);
        }

        try {
            // update transaction`s callback parameter
            $transaction->setCallBackParameters($request->all(), true);

            //read gateway property from transaction and make payment gateway handler
            $paymentGatewayHandler = $this->make($gateway, $transaction, $adapterConfig);

            //check that it's correct data and we can continue with this parameters
            if ($paymentGatewayHandler->canContinueWithCallbackParameters($request->all()) !== true) {
                throw new FailedTransactionException(trans('larapay::larapay.invalid_response'));
            }

            //get reference id from callback data
            $referenceId = $paymentGatewayHandler->getGatewayReferenceId();

            //search or transaction to detect double spending
            $doubleInvoice = LarapayTransaction::where('gate_refid', $referenceId)
                ->where('verified', true)//قبلا وریفای شده
                ->where('gate_name', $transaction->gate_name)
                ->first();

            //found double transaction
            if (!empty($doubleInvoice)) {
                // log double spending details
                XLog::emergency('referenceId double spending detected', [
                    'tag' => $referenceId,
                    'order_id' => $transaction->gateway_order_id,
                    'ips' => $request->ips(),
                    'gateway' => $gateway,
                ]);

                //update transaction and log double spending
                if (!preg_match('/DOUBLE_SPENDING/i', $transaction->description)) {
                    $transaction->description = "#DOUBLE_SPENDING_BY_{$doubleInvoice->id}#\n" . $transaction->description;
                    $transaction->save();
                }
                //throw new exception and stop verify transaction
                throw new FailedTransactionException(trans('larapay::larapay.could_not_verify_transaction'));
            }

            //set reference id on transaction
            $transaction->setReferenceId($referenceId);

            // verify start ----------------------------------------------------------------------------------------
            $verified = false;
            // try 3 times for verify transaction
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
                    usleep(500);
                    continue;
                }
            }

            //check if transaction verified
            if ($verified !== true) {
                XLog::error('transaction verification failed', ['tag' => $referenceId, 'gateway' => $gateway]);
                throw new FailedTransactionException(trans('larapay::larapay.verification_failed'));
            } else {
                XLog::info('invoice verified successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
            }
            // verify end ------------------------------------------------------------------------------------------

            // after verify start ----------------------------------------------------------------------------------
            $afterVerified = false;
            // try 3 times for after verify transaction
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
                } catch (Exception $e) {
                    XLog::error('Exception: ' . $e->getMessage(),
                        ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                    usleep(500);
                    continue;
                }
            }

            if ($afterVerified !== true) {
                XLog::error('transaction after verification failed',
                    ['tag' => $referenceId, 'gateway' => $gateway]);
                throw new FailedTransactionException(trans('larapay::larapay.after_verification_failed'));
            } else {
                XLog::info('invoice after verified successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
            }

            // after verify end ------------------------------------------------------------------------------------
        } catch (Exception $e) {
            XLog::emergency($e->getMessage() . ' code:' . $e->getCode() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new FailedTransactionException($e->getMessage(), $e->getCode(), $e);
        }

        //transaction done successfully
        XLog::info('invoice completed successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
        //set transaction date time
        $transaction->setPaidAt('now');
        //set accomplished true on transaction and save it.
        $transaction->setAccomplished(true);
        //return transaction
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

        XLog::info($name, $arguments);

        // چو ن همیشه متد ها با یک پارامتر کلی بصورت آرایه فراخوانی میشوند. مثلا:
        // $paymentGatewayHandler->generateForm($ArrayOfExtraPaymentParams)
        if (count($arguments) > 0) {
            $this->gateway->setParameters($arguments[0]); // set parameters
        }

        try {
            return call_user_func_array([$this->gateway, $name], $arguments); // call desire method
        } catch (Exception $e) {
            XLog::error($e->getMessage() . ' Code:' . $e->getCode() . ' File:' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }
}
