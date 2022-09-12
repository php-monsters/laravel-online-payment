<?php
declare(strict_types=1);

namespace Tartan\Larapay\Adapter;

use Tartan\Larapay\Adapter\Saderat\Exception;
use Tartan\Larapay\Adapter\Saderat\Helper;
use Tartan\Log\Facades\XLog;


/**
 * Class Saderat
 * @package Tartan\Larapay\Adapter
 */
class Saderat extends AdapterAbstract implements AdapterInterface
{
    protected $WSDL = 'https://sepehr.shaparak.ir:8081/V1/PeymentApi/GetToken';
    protected $endPoint = 'https://sepehr.shaparak.ir:8080/pay';
    protected $verifyWSDL = 'https://sepehr.shaparak.ir:8081/V1/PeymentApi/Advice';

    protected $testWSDL = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/V1/PeymentApi/GetToken';
    protected $testEndPoint = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/Pay';
    protected $testVerifyWSDL = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/V1/PeymentApi/Advice';

    protected $reverseSupport = false;

    /**
     * @return array
     * @throws Exception
     */
    protected function requestToken(): string
    {
        if ($this->getTransaction()->checkForRequestToken() == false) {
            throw new Exception('larapay::larapay.could_not_request_payment');
        }

        $this->checkRequiredParameters([
            'terminalid',
            'amount',
            'order_id',
            'redirect_url',
        ]);

        $sendParams = [
            "Amount" => $this->amount,
            "callbackURL" => $this->redirect_url,
            "invoiceID" => $this->order_id,
            "terminalID" => $this->terminalid,
        ];

        try {
            XLog::debug('reservation call', $sendParams);

            $result = Helper::post2https($sendParams, $this->getWSDL());
            $response = json_decode($result);

            XLog::info('reservation response', $response);

            if (isset($response->Accesstoken)) {

                if ($response->Status == 0) {
                    $this->getTransaction()->setGatewayToken(strval($response->Accesstoken)); // update transaction reference id
                    return $response->Accesstoken;
                } else {
                    throw new Exception($response->Status);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }
        } catch (\Exception $e) {
            throw new Exception('Saderat(Sepehr) Fault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function generateForm(): string
    {
        $token = $this->requestToken();
        $form = view('larapay::saderat-form', [
            'endPoint'    => $this->getEndPoint(),
            'token' => $token,
            'terminalID' => $this->terminalID,
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit' => boolval($this->auto_submit)
        ]);

        return $form->__toString();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function formParams(): array
    {
        $token = $this->requestToken();

        return  [
            'endPoint'    => $this->getEndPoint() . $token,
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
            'rrn',
            'tracenumber',
            'digitalreceipt',
            'respcode',
            'amount',
        ]);

        $sendParams = [
            "digitalreceipt" => $this->digitalreceipt,
            "Tid" => $this->terminalid,
        ];

        try {
            XLog::debug('PaymentVerification call', $sendParams);
            $result   = Helper::post2https($sendParams, $this->getVerifyWSDL());
            $response = json_decode($result);
            XLog::info('PaymentVerification response', $this->obj2array($response));

            if (isset($response->Status)) {
                if ($response->Status == "Ok" and $response->ReturnId == $this->getTransaction()->getPayableAmount()) {
                    $this->getTransaction()->setReferenceId(strval($this->rrn)); // update transaction reference id
                    $this->getTransaction()->setVerified();
                    return true;
                } else {
                    throw new Exception($response->Message);
                }
            } else {
                throw new Exception($response->Message);
            }
        } catch (\Exception $e) {
            throw new Exception('Saderat Fault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }


    /**
     * @return string
     */
    private function getVerifyWSDL(): string
    {
        if (config('larapay.mode') == 'production') {
            return $this->verifyWSDL;
        } else {
            return $this->testVerifyWSDL;
        }
    }

    /**
     * @return bool
     */
    public function canContinueWithCallbackParameters(): bool
    {
        if ($this->respcode == "0") {
            return true;
        }

        return false;
    }

    public function getGatewayReferenceId(): string
    {
        $this->checkRequiredParameters([
            'digitalreceipt',
        ]);

        return strval($this->digitalreceipt);
    }
}
