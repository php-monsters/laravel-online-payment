<?php

declare(strict_types=1);

namespace Tartan\Larapay\Adapter;

use SoapClient;
use SoapFault;
use Tartan\Larapay\Adapter\Saman\Exception;
use Tartan\Log\Facades\XLog;

/**
 * Class Saman
 * @package Tartan\Larapay\Adapter
 */
class Saman extends AdapterAbstract implements AdapterInterface
{
    protected $WSDL      = 'https://sep.shaparak.ir/payments/referencepayment.asmx?WSDL';
    protected $tokenWSDL = 'https://sep.shaparak.ir/Payments/InitPayment.asmx?WSDL';
    protected $endPoint = 'https://sep.shaparak.ir/Payment.aspx';

    protected $testWSDL      = 'http://banktest.ir/gateway/saman/payments/referencepayment?wsdl';
    protected $testTokenWSDL = 'http://banktest.ir/gateway/saman/Payments/InitPayment?wsdl';
    protected $testEndPoint  = 'http://banktest.ir/gateway/saman/gate';

    protected $reverseSupport = true;

    /**
     * @return string
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function requestToken(): string
    {
        XLog::debug(__METHOD__);

        if ($this->getTransaction()->checkForRequestToken() == false) {
            throw new Exception('larapay::larapay.could_not_request_payment');
        }

        $this->checkRequiredParameters([
            'merchant_id',
            'order_id',
            'amount',
            'redirect_url',
        ]);

        $sendParams = [
            'TermID'      => $this->merchant_id,
            'ResNum'      => $this->order_id,
            'TotalAmount' => intval($this->amount),
        ];

        try {
            $soapClient = $this->getSoapClient('token');

            XLog::debug('RequestToken call', $sendParams);

            $response = $soapClient->__soapCall('RequestToken', $sendParams);

            if (!empty($response)) {
                XLog::info('RequestToken response', ['response' => $response]);

                if (strlen($response) > 10) { // got string token
                    $this->getTransaction()->setGatewayToken(strval($response)); // update transaction reference id

                    return $response;
                } else {
                    throw new Exception($response); // negative integer as error
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }

        } catch (SoapFault $e) {
            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    public function generateForm(): \Illuminate\View\View
    {
        XLog::debug(__METHOD__);

        if ($this->with_token) {
            return $this->generateFormWithToken();
        } else {
            return $this->generateFormWithoutToken(); // default
        }
    }

    public function formParams(): array
    {
        if ($this->with_token) {
            return $this->formParamsWithToken();
        } else {
            return $this->formParamsWithoutToken(); // default
        }
    }

    protected function generateFormWithoutToken(): string
    {
        XLog::debug(__METHOD__, $this->getParameters());

        $this->checkRequiredParameters([
            'merchant_id',
            'amount',
            'order_id',
            'redirect_url',
        ]);

        return view('larapay::saman-form', [
            'endPoint'    => $this->getEndPoint(),
            'amount'      => intval($this->amount),
            'merchantId'  => $this->merchant_id,
            'orderId'     => $this->order_id,
            'redirectUrl' => $this->redirect_url,
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit'  => boolval($this->auto_submit),
        ]);
    }

    protected function formParamsWithoutToken(): array
    {
        XLog::debug(__METHOD__, $this->getParameters());

        $this->checkRequiredParameters([
            'merchant_id',
            'amount',
            'order_id',
            'redirect_url',
        ]);

        return  [
            'endPoint'    => $this->getEndPoint(),
            'amount'      => intval($this->amount),
            'merchantId'  => $this->merchant_id,
            'orderId'     => $this->order_id,
            'redirectUrl' => $this->redirect_url,
        ];
    }

    protected function generateFormWithToken(): string
    {
        XLog::debug(__METHOD__, $this->getParameters());
        $this->checkRequiredParameters([
            'merchant_id',
            'order_id',
            'amount',
            'redirect_url',
        ]);

        $token = $this->requestToken();

        XLog::info(__METHOD__, ['fetchedToken' => $token]);

        return view('larapay::saman-form', [
            'endPoint'    => $this->getEndPoint(),
            'amount'      => '',// just because of view
            'merchantId'  => '', // just because of view
            'orderId'     => '', // just because of view
            'token'       => $token,
            'redirectUrl' => $this->redirect_url,
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit'  => boolval($this->auto_submit),
        ]);
    }

    protected function formParamsWithToken(): array
    {
        XLog::debug(__METHOD__, $this->getParameters());
        $this->checkRequiredParameters([
            'merchant_id',
            'order_id',
            'amount',
            'redirect_url',
        ]);

        $token = $this->requestToken();

        XLog::info(__METHOD__, ['fetchedToken' => $token]);

        return [
            'endPoint'    => $this->getEndPoint(),
            'amount'      => '',// just because of view
            'merchantId'  => '', // just because of view
            'orderId'     => '', // just because of view
            'token'       => $token,
            'redirectUrl' => $this->redirect_url,
        ];
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function verifyTransaction(): bool
    {
        if ($this->getTransaction()->checkForVerify() == false) {
            throw new Exception('larapay::larapay.could_not_verify_payment');
        }

        $this->checkRequiredParameters([
            'State',
            'RefNum',
            'ResNum',
            'merchant_id',
            'TraceNo',
            'SecurePan'
        ]);

        if ($this->State != 'OK') {
            throw new Exception('Error: ' . $this->State);
        }

        try {
            $soapClient = $this->getSoapClient();

            XLog::info('VerifyTransaction call', [$this->RefNum, $this->merchant_id]);
            $response = $soapClient->VerifyTransaction($this->RefNum, $this->merchant_id);

            if (isset($response)) {
                XLog::info('VerifyTransaction response', ['response' => $response]);

                if ($response == $this->getTransaction()->getPayableAmount()) {
                    // double check the amount by transaction amount
                    $this->getTransaction()->setCardNumber(strval($this->SecurePan), false); // no save()
                    $this->getTransaction()->setVerified(); // with save()

                    return true;
                } else {
                    throw new Exception($response);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }

        } catch (SoapFault $e) {
            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function reverseTransaction(): bool
    {
        if ($this->reverseSupport == false || $this->getTransaction()->checkForReverse() == false) {
            throw new Exception('larapay::larapay.could_not_reverse_payment');
        }

        $this->checkRequiredParameters([
            'RefNum',
            'merchant_id',
            'merchant_pass',
            'amount',
        ]);

        try {
            $soapClient = $this->getSoapClient();

            XLog::info('reverseTransaction call', [$this->RefNum, $this->merchant_id]);
            $response = $soapClient->reverseTransaction1(
                $this->RefNum,
                $this->merchant_id,
                $this->merchant_pass,
                $this->amount
            );

            if (isset($response)) {
                XLog::info('reverseTransaction response', ['response' => $response]);

                if ($response == 1) { // check by transaction amount
                    $this->getTransaction()->setRefunded(true);

                    return true;
                } else {
                    throw new Exception($response);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }

        } catch (SoapFault $e) {
            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    /**
     * @return bool
     */
    public function canContinueWithCallbackParameters(): bool
    {
        try {
            $this->checkRequiredParameters([
                'RefNum',
                'State',
            ]);
        } catch (\Exception $e) {
            return false;
        }

        if ($this->State == 'OK') {
            return true;
        }

        return false;
    }

    /**
     * @return string
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    public function getGatewayReferenceId(): string
    {
        $this->checkRequiredParameters([
            'RefNum',
        ]);

        return strval($this->RefNum);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getWSDL($type = null): string
    {
        if (config('larapay.mode') == 'production') {
            switch (strtoupper($type)) {
                case 'TOKEN':
                    return $this->tokenWSDL;
                    break;
                default:
                    return $this->WSDL;
                    break;
            }
        } else {
            switch (strtoupper($type)) {
                case 'TOKEN':
                    return $this->testTokenWSDL;
                    break;
                default:
                    return $this->testWSDL;
                    break;
            }
        }
    }

    /**
     * @param string type
     *
     * @return SoapClient
     * @throws SoapFault
     */
    protected function getSoapClient($type = null): SoapClient
    {
        return new SoapClient($this->getWSDL($type), $this->getSoapOptions());
    }
}
