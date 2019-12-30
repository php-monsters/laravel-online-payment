<?php

namespace Tartan\Larapay\Adapter;

use Tartan\Larapay\Adapter\Saman\Exception;
use Illuminate\Support\Facades\Log;
use Tartan\Larapay\Adapter\Pasargad\Helper;

/**
 * Class Fake
 * @package Tartan\Larapay\Adapter
 */
class Fake extends AdapterAbstract implements AdapterInterface
{
    protected $baseUrl;
    protected $endPoint;

    protected $reverseSupport = true;

    public function init()
    {
        parent::init();

        $this->baseUrl  = config('larapay.fake.base_url');
    }

    /**
     * @return string
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function requestToken(): string
    {
        if ($this->getTransaction()->checkForRequestToken() == false) {
            throw new Exception('larapay::larapay.could_not_request_payment');
        }

        $this->checkRequiredParameters([
            'amount',
            'redirect_url',
        ]);

        $sendParams = [
            'amount'      => intval($this->amount),
            'order_id'    => $this->order_id,
            'merchant_id' => $this->merchant_id,
            'redirect'    => $this->redirect_url,
        ];

        try {
            Log::debug('PaymentRequest call', $sendParams);
            $result = Helper::post2https($sendParams, $this->baseUrl . '/api/token');


            $resultObj = json_decode($result);
            Log::info('PaymentRequest response', $this->obj2array($resultObj));


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

    public function generateForm(): string
    {
        Log::debug(__METHOD__, $this->getParameters());

        $token = $this->requestToken();

        Log::info(__METHOD__, ['fetchedToken' => $token]);

        return view('larapay::fake-form', [
            'endPoint'    => $this->getEndPoint(),
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit'  => boolval($this->auto_submit),
        ]);
    }

    /**
     * @return bool
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function verifyTransaction(): bool
    {
        return true;
    }

    /**
     * @return bool
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function reverseTransaction(): bool
    {
        return true;
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

        return $this->transId;
    }

    protected function getEndPoint(): string
    {
        if (config('larapay.mode') == 'production') {
            throw new \Tartan\Larapay\Adapter\Exception('You have used fake adapter in production environment!');
        } else {
            return $this->baseUrl . '/ipg/gateway';
        }
    }
}
