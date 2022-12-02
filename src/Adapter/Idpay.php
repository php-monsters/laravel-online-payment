<?php
declare(strict_types=1);

namespace Tartan\Larapay\Adapter;

use Tartan\Larapay\Adapter\Idpay\Exception;
use PhpMonsters\Log\Facades\XLog;


/**
 * Class Idpay
 * @package Tartan\Larapay\Adapter
 */
class Idpay extends AdapterAbstract implements AdapterInterface
{
    protected $WSDL = 'https://api.idpay.ir/v1.1/payment';
    protected $endPoint  = 'https://idpay.ir/p/ws/{order-id}';

    protected $testWSDL = 'https://api.idpay.ir/v1.1/payment';
    protected $testEndPoint = 'https://idpay.ir/p/ws-sandbox/{order-id}';

    public $endPointVerify = 'https://api.idpay.ir/v1.1/payment/verify';

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
            'order_id',
            'amount',
            'redirect_url',
        ]);

        $sendParams = [
            'order_id'  => $this->getTransaction()->bank_order_id,
            'amount'      => intval($this->amount),
            'desc' => $this->description ? $this->description : '',
            'mail'       => $this->email ? $this->email : '',
            'phone'      => $this->mobile ? $this->mobile : '',
            'callback' => $this->redirect_url,
        ];

        $header = [
            'Content-Type: application/json',
            'X-API-KEY:' .$this->merchant_id,
            'X-SANDBOX:' .$this->getSandbox()
        ];
        try {
            XLog::debug('PaymentRequest call', $sendParams);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->WSDL);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sendParams));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $response = curl_exec($ch);
            $ch_error = curl_error($ch);
            curl_close($ch);
            $result = json_decode($response);

            if (isset($result->error_code)) {
                throw new Exception($result->error_code);
            }

            XLog::info('PaymentRequest response', $this->obj2array($result));
            $this->getTransaction()->setGatewayToken(strval($result->id)); // update transaction reference id
            return $result->id;
        } catch(\Exception $e) {
            throw new Exception($e->getMessage());
        };
    }


    /**
     * @return string
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function generateForm(): string
    {
        $authority = $this->requestToken();

        $form = view('larapay::idpay-form', [
            'endPoint'    => strtr($this->getEndPoint(), ['{order-id}' => $authority]),
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
            return "0";
        } else {
            return "1";
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
            'merchant_id',
        ]);

        $sendParams = [
            'id'  => $this->getTransaction()->gate_refid,
            'order_id'     => $this->getTransaction()->bank_order_id,
        ];

        $header = [
            'Content-Type: application/json',
            'X-API-KEY:' .$this->merchant_id,
            'X-SANDBOX:' .$this->getSandbox()
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->endPointVerify);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sendParams));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $response = curl_exec($ch);
            $ch_error = curl_error($ch);
            curl_close($ch);
            XLog::debug('PaymentVerification call', $sendParams);
            $result = json_decode($response);
            XLog::info('PaymentVerification response', $this->obj2array($result));

            if (isset($result->status)) {

                if ($result->status == 100 || $result->status == 101) {
                    $this->getTransaction()->setVerified();
                    $this->getTransaction()->setReferenceId((string)$result->id);
                    return true;
                } else {
                    throw new Exception($result->status);
                }
            } else {
                throw new Exception($result->error_code);
            }

        } catch(\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function canContinueWithCallbackParameters(): bool
    {
        if (!empty($this->transaction['gate_refid'])) {
            return true;
        }

        return false;
    }

    public function getGatewayReferenceId(): string
    {
        $this->checkRequiredParameters([
            'merchant_id',
        ]);

        return strval($this->transaction['gate_refid']);
    }
}
