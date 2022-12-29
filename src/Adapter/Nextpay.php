<?php

declare(strict_types=1);

namespace PhpMonsters\Larapay\Adapter;

use PhpMonsters\Larapay\Adapter\Nextpay\Exception;
use PhpMonsters\Larapay\Adapter\Nextpay\Helper;
use PhpMonsters\Log\Facades\XLog;

/**
 * Class Nextpay
 * @package PhpMonsters\Larapay\Adapter
 */
class Nextpay extends AdapterAbstract implements AdapterInterface
{

    public $endPoint = 'https://nextpay.org/nx/gateway/token';
    public $endPointForm = 'https://nextpay.org/nx/gateway/payment/{trans_id}';
    public $endPointVerify = 'https://nextpay.org/nx/gateway/verify';

    public $reverseSupport = false;

    /**
     * @return array
     * @throws Exception
     * @throws \PhpMonsters\Larapay\Adapter\Exception
     */
    public function formParams(): array
    {
        $authority = $this->requestToken();

        return [
            'endPoint' => strtr($this->endPointForm, ['{trans_id}' => $authority]),
        ];
    }

    /**
     * @return bool
     */
    public function canContinueWithCallbackParameters(): bool
    {
        if (!empty($this->parameters['trans_id'])) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     * @throws \PhpMonsters\Larapay\Adapter\Exception
     */
    public function getGatewayReferenceId(): string
    {
        $this->checkRequiredParameters([
            'trans_id',
        ]);

        return strval($this->trans_id);
    }

    /**
     * @return string
     * @throws Exception
     * @throws \PhpMonsters\Larapay\Adapter\Exception
     */
    protected function generateForm(): string
    {
        $authority = $this->requestToken();

        $form = view('larapay::nextpay-form', [
            'endPoint' => strtr($this->endPointForm, ['{trans_id}' => $authority]),
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit' => true,
        ]);
        return $form->__toString();
    }

    /**
     * @return string
     * @throws Exception
     * @throws \PhpMonsters\Larapay\Adapter\Exception
     */
    protected function requestToken(): string
    {
        if ($this->getTransaction()->checkForRequestToken() === false) {
            throw new Exception('larapay::larapay.could_not_request_payment');
        }

        $this->checkRequiredParameters([
            'api_key',
            'amount',
            'redirect_url',
            'order_id',
        ]);

        $sendParams = [
            'api_key' => $this->api_key,
            'amount' => intval($this->amount),
            'order_id' => ($this->order_id),
            'payer_desc' => $this->description ? $this->description : '',
            'customer_phone' => $this->mobile ? $this->mobile : '',
            'callback_uri' => $this->redirect_url,
        ];

        try {
            XLog::debug('PaymentRequest call', $sendParams);
            $result = Helper::post2https($sendParams, $this->endPoint);
            $resultObj = json_decode($result);

            XLog::info('PaymentRequest response', $this->obj2array($resultObj));

            if (isset($resultObj->code)) {
                if ($resultObj->code == -1) {
                    $this->getTransaction()->setGatewayToken(strval($resultObj->trans_id)); // update transaction reference id
                    return $resultObj->trans_id;
                } else {
                    throw new Exception('larapay::larapay.nextpay.errors.error_'.$resultObj->code);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }
        } catch (\Exception $e) {
            throw new Exception('Nextpay Fault: '.$e->getMessage().' #'.$e->getCode(), $e->getCode());
        }
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \PhpMonsters\Larapay\Adapter\Exception
     */
    protected function verifyTransaction(): bool
    {
        if ($this->getTransaction()->checkForVerify() === false) {
            throw new Exception('larapay::larapay.could_not_verify_payment');
        }

        $this->checkRequiredParameters([
            'api_key',
            'trans_id',
            'amount'
        ]);

        $sendParams = [
            'api_key' => $this->api_key,
            'trans_id' => $this->trans_id,
            'amount' => $this->amount,
        ];

        try {
            XLog::debug('PaymentVerification call', $sendParams);
            $result = Helper::post2https($sendParams, $this->endPointVerify);
            $response = json_decode($result);
            XLog::info('PaymentVerification response', $this->obj2array($response));
            if (isset($response->code, $response->Shaparak_Ref_Id)) {
                if ($response->code == 0) {
                    $this->getTransaction()->setVerified();
                    $this->getTransaction()->setReferenceId(strval($response->Shaparak_Ref_Id)); // update transaction reference id
                    return true;
                } else {
                    throw new Exception('larapay::larapay.nextpay.errors.error_'.$response->code);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }
        } catch (\Exception $e) {
            throw new Exception('Nextpay Fault: '.$e->getMessage().' #'.$e->getCode(), $e->getCode());
        }
    }
}
