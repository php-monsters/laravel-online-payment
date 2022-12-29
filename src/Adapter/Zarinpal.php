<?php
declare(strict_types=1);

namespace PhpMonsters\Larapay\Adapter;

use SoapClient;
use SoapFault;
use PhpMonsters\Larapay\Adapter\Zarinpal\Exception;
use PhpMonsters\Log\Facades\XLog;

/**
 * Class Zarinpal
 * @package PhpMonsters\Larapay\Adapter
 */
class Zarinpal extends AdapterAbstract implements AdapterInterface
{
    protected $WSDL = 'https://www.zarinpal.com/pg/services/WebGate/wsdl';

    protected $endPoint       = 'https://www.zarinpal.com/pg/StartPay/{authority}';
    protected $zarinEndPoint  = 'https://www.zarinpal.com/pg/StartPay/{authority}/ZarinGate';
    protected $mobileEndPoint = 'https://www.zarinpal.com/pg/StartPay/{authority}/MobileGate';

    protected $testWSDL = 'https://banktest.ir/gateway/zarinpal/ws?wsdl';
    protected $testEndPoint = 'https://banktest.ir/gateway/zarinpal/gate/{authority}';

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
            'MerchantID'  => $this->merchant_id,
            'Amount'      => intval($this->amount),
            'Description' => $this->description ? $this->description : '',
            'Email'       => $this->email ? $this->email : '',
            'Mobile'      => $this->mobile ? $this->mobile : '',
            'CallbackURL' => $this->redirect_url,
        ];

        try {
            $soapClient = new SoapClient($this->getWSDL());

            XLog::debug('PaymentRequest call', $sendParams);

            $response = $soapClient->PaymentRequest($sendParams);

            XLog::info('PaymentRequest response', $this->obj2array($response));


            if (isset($response->Status)) {

                if ($response->Status == 100) {
                    $this->getTransaction()->setGatewayToken(strval($response->Authority)); // update transaction reference id

                    return $response->Authority;
                } else {
                    throw new Exception($response->Status);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }
        } catch (SoapFault $e) {
            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
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
            'endPoint'    => strtr($this->getEndPoint(), ['{authority}' => $authority]),
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit'  => boolval($this->auto_submit),
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

        return  [
            'endPoint'    => strtr($this->getEndPoint(), ['{authority}' => $authority]),
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
            'MerchantID' => $this->merchant_id,
            'Authority'  => $this->Authority,
            'Amount'     => intval($this->transaction->amount),
        ];

        try {
            $soapClient = new SoapClient($this->getWSDL());

            XLog::debug('PaymentVerification call', $sendParams);

            $response = $soapClient->PaymentVerification($sendParams);

            XLog::info('PaymentVerification response', $this->obj2array($response));


            if (isset($response->Status, $response->RefID)) {

                if ($response->Status == 100) {
                    $this->getTransaction()->setVerified();
                    $this->getTransaction()->setReferenceId((string)$response->RefID); // update transaction reference id

                    return true;
                } else {
                    throw new Exception($response->Status);
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
