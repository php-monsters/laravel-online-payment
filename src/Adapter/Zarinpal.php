<?php
declare(strict_types=1);

namespace PhpMonsters\Larapay\Adapter;

use Illuminate\Support\Facades\Http;
use PhpMonsters\Larapay\Adapter\Zarinpal\Exception;
use PhpMonsters\Log\Facades\XLog;

/**
 * Class Zarinpal
 * @package PhpMonsters\Larapay\Adapter
 */
class Zarinpal extends AdapterAbstract implements AdapterInterface
{
    protected $paymentRequestEndPoint = "https://payment.zarinpal.com/pg/v4/payment/request.json";
    protected $paymentVerifyEndPoint = "https://payment.zarinpal.com/pg/v4/payment/verify.json";


    protected $endPoint = 'https://www.zarinpal.com/pg/StartPay/{authority}';
    protected $zarinEndPoint = 'https://www.zarinpal.com/pg/StartPay/{authority}/ZarinGate';
    protected $mobileEndPoint = 'https://www.zarinpal.com/pg/StartPay/{authority}/MobileGate';

    protected $testEndPoint = 'https://sandbox.banktest.ir/zarinpal/www.zarinpal.com/pg/StartPay/{authority}';
    protected $testPaymentRequestEndPoint = "https://sandbox.banktest.ir/zarinpal/api.zarinpal.com/pg/v4/payment/request.json";
    protected $testPaymentVerifyEndPoint = "https://sandbox.banktest.ir/zarinpal/api.zarinpal.com/pg/v4/payment/verify.json";


    public $reverseSupport = false;

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
            'merchant_id',
            'amount',
            'redirect_url',
        ]);

        $sendParams = [
            'merchant_id' => $this->merchant_id,
            'amount' => intval($this->amount),
            'description' => $this->description ? $this->description : '',
            "metadata" => [
                'mobile' => $this->mobile ? $this->mobile : '',
                'email' => $this->email ? $this->email : '',
            ],
            'callback_url' => $this->redirect_url,
        ];

        try {
            XLog::debug('PaymentRequest call', $sendParams);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->getPaymentRequestEndpPoint(), $sendParams);

            $response->throw();

            $result = $response->object();

            if (empty($result->errors)) {
                if ($result->data->code == 100) {
                    $this->getTransaction()->setGatewayToken(strval($result->data->authority));  // update transaction reference id

                    return $result->data->authority;
                } else {
                    throw new Exception('no error provided and not 100');
                }
            } else {
                throw new Exception('code: ' . $result->errors->code . "\n" . 'message: ' . $result->errors->message);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @return string
     * @throws Exception
     * @throws \PhpMonsters\Larapay\Adapter\Exception
     */
    protected function generateForm(): string
    {
        $authority = $this->requestToken();

        $form = view('larapay::zarinpal-form', [
            'endPoint' => strtr($this->getEndPoint(), ['{authority}' => $authority]),
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit' => boolval($this->auto_submit),
        ]);

        return $form->__toString();
    }

    /**
     * @return array
     * @throws Exception
     * @throws \PhpMonsters\Larapay\Adapter\Exception
     */
    public function formParams(): array
    {
        $authority = $this->requestToken();

        return [
            'endPoint' => strtr($this->getEndPoint(), ['{authority}' => $authority]),
        ];
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \PhpMonsters\Larapay\Adapter\Exception
     */
    protected function verifyTransaction(): bool
    {
        if ($this->getTransaction()->checkForVerify() == false) {
            throw new Exception('larapay::larapay.could_not_verify_payment');
        }

        $this->checkRequiredParameters([
            'merchant_id',
            'Authority',
        ]);

        $sendParams = [
            'merchant_id' => $this->merchant_id,
            'authority' => $this->Authority,
            'amount' => intval($this->transaction->amount),
        ];

        XLog::debug('PaymentVerification call', $sendParams);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->getPaymentVerifyEndpPoint(), $sendParams);

            $response->throw();

            $result = $response->object();

            XLog::info('PaymentVerification response', $response->json());

            if ($result->data->code === 100) {
                $this->getTransaction()->setVerified();
                $this->getTransaction()->setReferenceId((string) $result->data->ref_id); // update transaction reference id
                return true;
            } else if ($result->data->code === 101) {
                return true;
            } else {
                throw new Exception('code: ' . $result->errors->code . "\n" . 'message: ' . $result->errors->message);
            }
        } catch (\Exception $e) {
            throw new Exception("Payment Verify Error: " . $e->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function canContinueWithCallbackParameters(): bool
    {
        if ($this->Status == "OK") {
            return true;
        }

        return false;
    }

    public function getGatewayReferenceId(): string
    {
        $this->checkRequiredParameters([
            'Authority',
        ]);

        return strval($this->Authority);
    }
}
