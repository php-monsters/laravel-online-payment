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
        $adapterName      = $adapterNamespace . $adapter;

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


    public function routes(array $options = [])
    {
        Route::get('payment/go-to-bank', 'LarapayController@show')->name('larapay.transfer');
        Route::post('payment/callback', 'LarapayController@handleCallback')->name('larapay.callback');
    }

    public function verifyTransaction(Request $request)
    {
        $gateway             = $request->input('gateway');
        $transactionId = $request->input('transactionId');

        //TODO find transaction and do other
        XLog::debug('request: ', $request->all());

        $referenceId = '';
        $paidTime = '';
        $amount = '';

        throw new FailedTransactionException(__('Code N2 - Transaction not found'));

        do {
            try {
                // find the transaction by token
                $transaction =  LarapayTransaction::find($transactionId);

                if (!$transaction) {
                    throw new FailedTransactionException(__('Code N2 - Transaction not found'));
                }


                if ($transaction->gate_name != $gateway) {
                    return view('installment::callback')->withErrors([__('Code N3 - Transaction not found')]);
                }

                // update transaction`s callback parameter
                $transaction->setCallBackParameters($request->all());

                // load payment gateway properties
                //$gatewayProperties = json_decode($transaction->gateway->properties, true);

                // ایجاد یک instance از کامپوننت Larapay
                $paymentGatewayHandler = $this->make($gateway, $transaction, []);

                // با توجه به پارمترهای بازگشتی از درگاه پرداخت آیا امکان ادامه فرایند وجود دارد یا خیر؟
                if ($paymentGatewayHandler->canContinueWithCallbackParameters($request->all()) !== true) {
                    Session::flash('alert-danger', trans('gate.could_not_continue_because_of_callback_params'));
                    break;
                }

                // گرفتن Reference Number از پارامترهای دریافتی از درگاه پرداخت
                $referenceId = $paymentGatewayHandler->getGatewayReferenceId($request->all());

                // جلوگیری از double spending یک شناسه مرجع تراکنش
                //TODO create task for get this
                $doubleInvoice = LarapayTransaction::where('gate_refid', $referenceId)
                                                       ->where('verified', true)//قبلا وریفای شده
                                                       ->where('gate_name', $transaction->gate_name)
                                                       ->first();


                if (!empty($doubleInvoice)) {
                    // double spending شناسایی شد
                    XLog::emergency('referenceId double spending detected', [
                        'tag'      => $referenceId,
                        'order_id' => $transaction->gateway_order_id,
                        'ips'      => $request->ips(),
                        'gateway'  => $gateway,
                    ]);
                    Session::flash('alert-danger', trans('gate.double_spending'));
                    // آپدیت کردن توصیحات فاکتور
                    if (!preg_match('/DOUBLE_SPENDING/i', $transaction->description)) {
                        $transaction->description = "#DOUBLE_SPENDING_BY_{$doubleInvoice->id}#\n" . $transaction->description;
                        $transaction->save();
                    }
                    break;
                }

                $transaction->setReferenceId($referenceId);

                // verify start ----------------------------------------------------------------------------------------
                $verified = false;
                // سه بار تلاش برای تایید تراکنش
                for ($i = 1; $i <= 3; $i++) {
                    try {
                        XLog::info('trying to verify payment',
                            ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);

                        $verifyResult = $paymentGatewayHandler->verify($request->all());
                        if ($verifyResult) {
                            $verified = true;
                        }
                        XLog::info('verify result',
                            ['result' => $verifyResult, 'try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                        break;
                    } catch (\Exception $e) {
                        XLog::error('Exception: ' . $e->getMessage(),
                            ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                        continue;
                    }
                }

                if ($verified !== true) {
                    XLog::error('transaction verification failed', ['tag' => $referenceId, 'gateway' => $gateway]);
                    break;
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

                        $afterVerifyResult = $paymentGatewayHandler->afterVerify($request->all());
                        if ($afterVerifyResult) {
                            $afterVerified = true;
                        }
                        XLog::info('after verify result', [
                            'result'  => $afterVerifyResult,
                            'try'     => $i,
                            'tag'     => $referenceId,
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
                    break;
                } else {
                    XLog::info('invoice after verified successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
                }
                // after verify end ------------------------------------------------------------------------------------

                $paidSuccessfully = true;

            } catch (Exception $e) {
                XLog::emergency($e->getMessage() . ' code:' . $e->getCode() . ' ' . $e->getFile() . ':' . $e->getLine());
                break;
            }

            // Start to serve to customer  -----------------------------------------------------------------------------

            $ifCustomerServed = false;
            // write your code login and serve to your customer and set $ifCustomerServed to TRUE

            // End customer serve  -------------------------------------------------------------------------------------

            if (!$ifCustomerServed) {
                // خدمات به مشتری ارائه نشد
                // reverse start ---------------------------------------------------------------------------------
                // سه بار تلاش برای برگشت زدن تراکنش
                $reversed = false;
                for ($i = 1; $i <= 3; $i++) {
                    try {
                        // ایجاد پازامترهای مورد نیاز برای برگشت زدن فاکتور
                        $reverseParameters = $request->all();

                        $reverseResult = $paymentGatewayHandler->reverse($reverseParameters);
                        if ($reverseResult) {
                            $reversed = true;
                        }

                        break;
                    } catch (Exception $e) {
                        XLog::error('Exception: ' . $e->getMessage(), ['try' => $i, 'tag' => $referenceId]);
                        continue;
                    }
                }

                if ($reversed !== true) {
                    XLog::error('invoice reverse failed', ['tag' => $referenceId]);
                    Flash::error(trans('gate.transaction_reversed_failed'));
                    break;
                } else {
                    XLog::info('invoice reversed successfully', ['tag' => $referenceId]);
                    Flash::success(trans('gate.transaction_reversed_successfully'));
                }
                // end reverse -----------------------------------------------------------------------------------
            } else {
                // خدمات به مشتری ارائه شد
                Flash::success(trans('gate.invoice_paid_successfully'));
                Log::info('invoice completed successfully', ['tag' => $referenceId, 'gateway' => $paidGateway->slug]);
                // فلگ زدن فاکتور بعنوان فاکتور موفق
                $transaction->setAccomplished(true);
            }

        } while (false); // do not repeat

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
            $this->gateway->setParameters($arguments[ 0 ]); // set parameters
        }

        try {
            return call_user_func_array([$this->gateway, $name], $arguments); // call desire method
        } catch (\Exception $e) {
            Log::error($e->getMessage() . ' Code:' . $e->getCode() . ' File:' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }
}
