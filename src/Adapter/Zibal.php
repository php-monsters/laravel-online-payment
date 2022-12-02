<?php
declare(strict_types=1);

namespace Tartan\Larapay\Adapter;

use Tartan\Larapay\Adapter\Zibal\Exception;
use Tartan\Larapay\Adapter\Zibal\Helper;
use PhpMonsters\Log\Facades\XLog;


/**
 * Class Zibal
 * @package Tartan\Larapay\Adapter
 */
class Zibal extends AdapterAbstract implements AdapterInterface
{
    protected $WSDL = 'https://gateway.zibal.ir/v1/request';
    protected $endPoint  = 'https://gateway.zibal.ir/start/{trackId}';

    protected $testWSDL = 'https://gateway.zibal.ir/v1/request';
    protected $testEndPoint = 'https://gateway.zibal.ir/start/{trackId}';

    public $endPointVerify = 'https://gateway.zibal.ir/v1/verify';

    public $reverseSupport = false;

    /**
     * @return string
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function requestToken(): string
    {
        if ($this->getTransaction()->checkForRequestToken() == false) {
            throw new Exception('larapay::larapay.could_not_request_payment');
        }

        $this->checkRequiredParameters([
            'merchant_id',
            'amount',
            'redirect_url',
        ]);

        $sendParams = [
            'merchant'  => $this->getSandbox(),
            'orderId'  => $this->getTransaction()->bank_order_id,
            'amount'      => intval($this->amount),
            'description' => $this->description ? $this->description : '',
            'mobile'      => $this->mobile ? $this->mobile : '',
            'callbackUrl' => $this->redirect_url,
        ];

        try {
            XLog::debug('PaymentRequest call', $sendParams);
            $result = Helper::post2https($sendParams, $this->WSDL);
            $resultObj = json_decode($result);

            XLog::info('PaymentRequest response', $this->obj2array($resultObj));

            if (isset($resultObj->result)) {
                if ($resultObj->result == 100) {
                    $this->getTransaction()->setGatewayToken(strval($resultObj->trackId)); // update transaction reference id
                    return strval($resultObj->trackId);
                } else {
                    throw new Exception($resultObj->result);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

    }


    /**
     * @return string
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function generateForm(): string
    {

        $authority = $this->requestToken();

        $form = view('larapay::zibal-form', [
            'endPoint'    => strtr($this->getEndPoint(), ['{trackId}' => $authority]),
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit'  => boolval($this->auto_submit),
        ]);

        return $form->__toString();
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
            'endPoint'    => strtr($this->getEndPoint(), ['{authority}' => $authority]),
        ];
    }

    public function getSandbox(): string
    {
        if (config('larapay.mode') == 'production') {
            return $this->merchant_id;
        } else {
            return "zibal";
        }
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
            'status',
            'trackid',
        ]);

        $sendParams = [
            'merchant'  => $this->getSandbox(),
            'trackId'     => $this->trackid,
        ];


        try {
            XLog::debug('PaymentRequest call', $sendParams);
            $result = Helper::post2https($sendParams, $this->endPointVerify);
            $resultObj = json_decode($result);

            XLog::info('PaymentRequest response', $this->obj2array($resultObj));
            if (isset($resultObj->result)) {
                if ($resultObj->result == 100 || $resultObj->result == 201) {
                    $this->getTransaction()->setVerified();
                    $this->getTransaction()->setReferenceId((string)$this->trackId);
                    return true;
                } else {
                    throw new Exception($resultObj->result);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    /**
     * @return bool
     */
    public function canContinueWithCallbackParameters(): bool
    {

        if ($this->success == 1) {
            return true;
        }

        return false;
    }

    public function getGatewayReferenceId(): string
    {
        $test = (array)$this->checkRequiredParameters([
            'trackid',
        ]);
        return strval($this->trackid);

    }
}
