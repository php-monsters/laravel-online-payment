<?php
declare(strict_types=1);

namespace Tartan\Larapay\Adapter;

use Tartan\Larapay\Adapter\Zarinpal\Exception;
use Tartan\Log\Facades\XLog;

/**
 * Class Payir
 * @package Tartan\Larapay\Adapter
 */
class Payir extends AdapterAbstract implements AdapterInterface
{

    public $endPoint       = 'https://pay.ir/payment/send';
    public $endPointForm   = 'https://pay.ir/payment/gateway/';
    public $endPointVerify = 'https://pay.ir/payment/verify';

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
            'api',
            'amount',
            'redirect_url',
            'order_id',
        ]);

        $sendParams = [
            'api'          => $this->api,
            'amount'       => intval($this->amount),
            'factorNumber' => ($this->order_id),
            'description'  => $this->description ? $this->description : '',
            'mobile'       => $this->mobile ? $this->mobile : '',
            'redirect'     => $this->redirect_url,
        ];

        try {
            XLog::debug('PaymentRequest call', $sendParams);
            $result = Helper::post2https($sendParams, $this->endPoint);


            $resultObj = json_decode($result);
            XLog::info('PaymentRequest response', $this->obj2array($resultObj));


            if (isset($resultObj->status)) {

                if ($resultObj->status == 1) {
                    $this->getTransaction()->setGatewayToken($resultObj->transId); // update transaction reference id

                    return $resultObj->transId;
                } else {
                    throw new Exception($resultObj->status);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }
        } catch (\Exception $e) {
            throw new Exception('PayIr Fault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
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

        return view('larapay::payir-form', [
            'endPoint'    => $this->endPointForm . $authority,
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit'  => true,
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
            'endPoint'    => $this->endPointForm . $authority,
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
            'api',
            'transId',
        ]);

        $sendParams = [
            'api'     => $this->api,
            'transId' => $this->transId,
        ];

        try {

            XLog::debug('PaymentVerification call', $sendParams);
            $result   = Helper::post2https($sendParams, $this->endPointVerify);
            $response = json_decode($result);
            XLog::info('PaymentVerification response', $this->obj2array($response));


            if (isset($response->status, $response->amount)) {

                if ($response->status == 1) {
                    $this->getTransaction()->setVerified();
                    $this->getTransaction()->setReferenceId(strval($this->transId)); // update transaction reference id

                    return true;
                } else {
                    throw new Exception($response->status);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }

        } catch (\Exception $e) {

            throw new Exception('PayIr: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    /**
     * @return bool
     */
    public function canContinueWithCallbackParameters(): bool
    {

        if (!empty($this->parameters['transId'])) {
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
            'transId',
        ]);

        return strval($this->transId);
    }
}
