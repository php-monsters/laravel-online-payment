<?php

declare(strict_types=1);

namespace Tartan\Larapay\Adapter;

use SoapFault;
use Tartan\Larapay\Adapter\Parsian\Exception;
use Tartan\Log\Facades\XLog;

/**
 * Class Parsian
 * @package Tartan\Larapay\Adapter
 */
class Parsian extends AdapterAbstract implements AdapterInterface
{
    protected $WSDLSale     = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL';
    protected $WSDLConfirm  = 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL';
    protected $WSDLReversal = 'https://pec.shaparak.ir/NewIPGServices/Reverse/ReversalService.asmx';
    protected $endPoint     = 'https://pec.shaparak.ir/NewIPG/';

    protected $testWSDLSale     = 'http://banktest.ir/gateway/parsian-sale/ws?wsdl';
    protected $testWSDLConfirm  = 'http://banktest.ir/gateway/parsian-confirm/ws?wsdl';
    protected $testWSDLReversal = 'http://banktest.ir/gateway/parsian-reverse/ws?wsdl';
    protected $testEndPoint     = 'http://banktest.ir/gateway/parsian/gate';

    protected $reverseSupport = true;

    protected $requestType = '';

    protected $soapOptions = array(
        'soap_version' => 'SOAP_1_1',
        'cache_wsdl'   => WSDL_CACHE_NONE,
        'encoding'     => 'UTF-8',
    );


    public function init()
    {
        ini_set("default_socket_timeout", config('larapay.parsian.timeout'));
    }

    /**
     * @return array
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function requestToken()
    {
        if ($this->getTransaction()->checkForRequestToken() == false) {
            throw new Exception('larapay::larapay.could_not_request_payment');
        }

        $this->checkRequiredParameters([
            'pin',
            'order_id',
            'amount',
            'redirect_url',
        ]);

        $sendParams = [
            'LoginAccount'   => $this->pin,
            'Amount'         => intval($this->amount),
            'OrderId'        => intval($this->order_id),
            'CallBackUrl'    => $this->redirect_url,
            'AdditionalData' => $this->additional_data ? $this->additional_data : '',
        ];

        try {
            $this->requestType = 'request';
            $soapClient        = $this->getSoapClient();

            XLog::debug('SalePaymentRequest call', $sendParams);

            $response = $soapClient->SalePaymentRequest(array("requestData" => $sendParams));

            XLog::debug('SalePaymentRequest response', $this->obj2array($response));

            if (isset($response->SalePaymentRequestResult->Status, $response->SalePaymentRequestResult->Token)) {
                if ($response->SalePaymentRequestResult->Status == 0) {
                    $this->getTransaction()->setGatewayToken($response->SalePaymentRequestResult->Token); // update transaction reference id

                    return $response->SalePaymentRequestResult->Token;
                } else {
                    throw new Exception($this->SalePaymentRequestResult->Status);
                }
            } else {
                throw new Exception('larapay::parsian.errors.invalid_response');
            }
        } catch (SoapFault $e) {
            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function generateForm(): string
    {
        $authority = $this->requestToken();

        return view('larapay::parsian-form', [
            'endPoint'    => $this->getEndPoint(),
            'refId'       => $authority,
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit'  => boolval($this->auto_submit),
        ]);
    }

    /**
     * @return array
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    public function formParams(): array
    {
        $authority = $this->requestToken();

        return  [
            'endPoint'    => $this->getEndPoint(),
            'refId'       => $authority,
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
        $this->requestType = 'confirm';

        $this->checkRequiredParameters([
            'Token',
        ]);

        if ($this->status !== '0') {
            throw new Exception('larapay::parsian.errors.could_not_continue_with_non0_rs');
        }

        $sendParams = [
            'LoginAccount' => $this->pin,
            'Token'        => $this->Token,
        ];


        try {
            $soapClient = $this->getSoapClient();


            XLog::debug('ConfirmPayment call', $sendParams);

            $response = $soapClient->ConfirmPayment(array("requestData" => $sendParams));

            XLog::debug('ConfirmPayment response', $this->obj2array($response));

            if (isset($response->ConfirmPaymentResult)) {
                if ($response->ConfirmPaymentResult->Status == 0) {
                    $this->getTransaction()->setVerified();

                    return true;
                } else {
                    throw new Exception($response->ConfirmPaymentResult->Status);
                }
            } else {
                throw new Exception('larapay::parsian.errors.invalid_response');
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

        $this->requestType = 'reversal';


        $this->checkRequiredParameters([
            'Token',
        ]);

        $sendParams = [
            'LoginAccount' => $this->pin,
            'Token'        => $this->Token,
        ];

        try {
            $soapClient = $this->getSoapClient();
            XLog::debug('ReversalRequest call', $sendParams);

            $response = $soapClient->ReversalRequest(array("requestData" => $sendParams));

            XLog::debug('ReversalRequest response', $this->obj2array($response));

            if (isset($response->ReversalRequestResult->Status)) {
                if ($response->ReversalRequestResult->Status == 0) {
                    $this->getTransaction()->setRefunded();

                    return true;
                } else {
                    throw new Exception($response->ReversalRequestResult->Status);
                }
            } else {
                throw new Exception('larapay::parsian.errors.invalid_response');
            }
        } catch (SoapFault $e) {
            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    public function getGatewayReferenceId(): string
    {
        $this->checkRequiredParameters([
            'Token',
        ]);

        return strval($this->Token);
    }


    protected function getWSDL(): string
    {

        $type = $this->requestType;

        switch ($type) {
            case 'request':
                if (config('larapay.mode') == 'production') {
                    return $this->WSDLSale;
                } else {
                    return $this->testWSDLSale;
                }
                break;
            case 'confirm':
                if (config('larapay.mode') == 'production') {
                    return $this->WSDLConfirm;
                } else {
                    return $this->testWSDLConfirm;
                }
                break;
            case 'reversal':
                if (config('larapay.mode') == 'production') {
                    return $this->WSDLReversal;
                } else {
                    return $this->testWSDLReversal;
                }
                break;
        }

    }
}
