<?php
namespace Tartan\Larapay\Adapter;

use SoapFault;
use Tartan\Larapay\Adapter\Mellat\Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class Mellat
 * @package Tartan\Larapay\Adapter
 */
class Mellat extends AdapterAbstract implements AdapterInterface
{
    protected $WSDL = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    protected $endPoint = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';

    protected $testWSDL = 'http://banktest.ir/gateway/mellat/ws?wsdl';
    protected $testEndPoint = 'http://banktest.ir/gateway/mellat/gate';

    protected $reverseSupport = true;

    /**
     * @return array
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function requestToken()
    {
        if($this->getTransaction()->checkForRequestToken() == false) {
            throw new Exception('larapay::larapay.could_not_request_payment');
        }

        $this->checkRequiredParameters([
            'terminal_id',
            'username',
            'password',
            'order_id',
            'amount',
            'redirect_url',
        ]);

        $sendParams = [
            'terminalId'     => intval($this->terminal_id),
            'userName'       => $this->username,
            'userPassword'   => $this->password,
            'orderId'        => intval($this->order_id),
            'amount'         => intval($this->amount),
            'localDate'      => $this->local_date ? $this->local_date : date('Ymd'),
            'localTime'      => $this->local_time ? $this->local_time : date('His'),
            'additionalData' => $this->additional_data ? $this->additional_data : '',
            'callBackUrl'    => $this->redirect_url,
            'payerId'        => intval($this->payer_id),
        ];

        try {
            $soapClient = $this->getSoapClient();

            Log::debug('bpPayRequest call', $sendParams);

            $response = $soapClient->bpPayRequest($sendParams);

            if (isset($response->return)) {
                Log::info('bpPayRequest response', ['return' => $response->return]);

                $response = explode(',', $response->return);

                if ($response[0] == 0) {
                    $this->getTransaction()->setGatewayToken($response[1]); // update transaction reference id
                    return $response[1];
                }
                else {
                    throw new Exception($response[0]);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }
        } catch (SoapFault $e) {
            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function generateForm ()
    {
        $refId = $this->requestToken();

        return view('larapay::mellat-form', [
            'endPoint'    => $this->getEndPoint(),
            'refId'       => $refId,
            'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
            'autoSubmit'  => boolval($this->auto_submit)
        ]);
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function verifyTransaction ()
    {
        if($this->getTransaction()->checkForVerify() == false) {
            throw new Exception('larapay::larapay.could_not_verify_payment');
        }

        $this->checkRequiredParameters([
            'terminal_id',
            'username',
            'password',
            'RefId',
            'ResCode',
            'SaleOrderId',
            'SaleReferenceId',
            'CardHolderInfo'
        ]);

        $sendParams = [
            'terminalId'      => intval($this->terminal_id),
            'userName'        => $this->username,
            'userPassword'    => $this->password,
            'orderId'         => intval($this->SaleOrderId), // same as SaleOrderId
            'saleOrderId'     => intval($this->SaleOrderId),
            'saleReferenceId' => intval($this->SaleReferenceId)
        ];

        $this->getTransaction()->setCardNumber($this->CardHolderInfo);

        try {
            $soapClient = $this->getSoapClient();

            Log::debug('bpVerifyRequest call', $sendParams);

            //$response   = $soapClient->__soapCall('bpVerifyRequest', $sendParams);
            $response   = $soapClient->bpVerifyRequest($sendParams);

            if (isset($response->return)) {
                Log::info('bpVerifyRequest response', ['return' => $response->return]);

                if($response->return != '0') {
                    throw new Exception($response->return);
                } else {
                    $this->getTransaction()->setVerified();
                    return true;
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
    public function inquiryTransaction ()
    {
        if($this->getTransaction()->checkForInquiry() == false) {
            throw new Exception('larapay::larapay.could_not_inquiry_payment');
        }

        $this->checkRequiredParameters([
            'terminal_id',
            'terminal_user',
            'terminal_pass',
            'RefId',
            'ResCode',
            'SaleOrderId',
            'SaleReferenceId',
            'CardHolderInfo'
        ]);

        $sendParams = [
            'terminalId'      => intval($this->terminal_id),
            'userName'        => $this->username,
            'userPassword'    => $this->password,
            'orderId'         => intval($this->SaleOrderId), // same as SaleOrderId
            'saleOrderId'     => intval($this->SaleOrderId),
            'saleReferenceId' => intval($this->SaleReferenceId)
        ];

        $this->getTransaction()->setCardNumber($this->CardHolderInfo);

        try {
            $soapClient = $this->getSoapClient();

            Log::debug('bpInquiryRequest call', $sendParams);
            //$response   = $soapClient->__soapCall('bpInquiryRequest', $sendParams);
            $response   = $soapClient->bpInquiryRequest($sendParams);

            if (isset($response->return)) {
                Log::info('bpInquiryRequest response', ['return' => $response->return]);
                if($response->return != '0') {
                    throw new Exception($response->return);
                } else {
                    $this->getTransaction()->setVerified();
                    return true;
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }

        } catch (SoapFault $e) {

            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }
    }

    /**
     * Send settle request
     *
     * @return bool
     *
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function settleTransaction()
    {
        if ($this->getTransaction()->checkForAfterVerify() == false) {
            throw new Exception('larapay::larapay.could_not_settle_payment');
        }

        $this->checkRequiredParameters([
            'terminal_id',
            'username',
            'password',
            'RefId',
            'ResCode',
            'SaleOrderId',
            'SaleReferenceId',
            'CardHolderInfo'
        ]);

        $sendParams = [
            'terminalId'      => intval($this->terminal_id),
            'userName'        => $this->username,
            'userPassword'    => $this->password,
            'orderId'         => intval($this->SaleOrderId), // same as orderId
            'saleOrderId'     => intval($this->SaleOrderId),
            'saleReferenceId' => intval($this->SaleReferenceId)
        ];

        try {
            $soapClient = $this->getSoapClient();

            Log::debug('bpSettleRequest call', $sendParams);
            //$response = $soapClient->__soapCall('bpSettleRequest', $sendParams);
            $response = $soapClient->bpSettleRequest($sendParams);

            if (isset($response->return)) {
                Log::info('bpSettleRequest response', ['return' => $response->return]);

                if($response->return == '0' || $response->return == '45') {
                    $this->getTransaction()->setAfterVerified();
                    return true;
                } else {
                    throw new Exception($response->return);
                }
            } else {
                throw new Exception('larapay::larapay.invalid_response');
            }

        } catch (\SoapFault $e) {
            throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
        }

    }

    /**
     * @return bool
     * @throws Exception
     * @throws \Tartan\Larapay\Adapter\Exception
     */
    protected function reverseTransaction ()
    {
        if ($this->reverseSupport == false || $this->getTransaction()->checkForReverse() == false) {
            throw new Exception('larapay::larapay.could_not_reverse_payment');
        }

        $this->checkRequiredParameters([
            'terminal_id',
            'username',
            'password',
            'RefId',
            'ResCode',
            'SaleOrderId',
            'SaleReferenceId',
            'CardHolderInfo'
        ]);

        $sendParams = [
            'terminalId'      => intval($this->terminal_id),
            'userName'        => $this->username,
            'userPassword'    => $this->password,
            'orderId'         => intval($this->SaleOrderId), // same as orderId
            'saleOrderId'     => intval($this->SaleOrderId),
            'saleReferenceId' => intval($this->SaleReferenceId)
        ];

        try {
            $soapClient = $this->getSoapClient();

            Log::debug('bpReversalRequest call', $sendParams);
            //$response = $soapClient->__soapCall('bpReversalRequest', $sendParams);
            $response = $soapClient->bpReversalRequest($sendParams);

            Log::info('bpReversalRequest response', ['return' => $response->return]);

            if (isset($response->return)){
                if ($response->return == '0' || $response->return == '45') {
                    $this->getTransaction()->setRefunded();
                    return true;
                } else {
                    throw new Exception($response->return);
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
        if ($this->ResCode === "0" || $this->ResCode === 0) {
            return true;
        }
        return false;
    }

    public function getGatewayReferenceId(): string
    {
        $this->checkRequiredParameters([
            'RefId',
        ]);
        return $this->RefId;
    }

    public function afterVerify()
    {
        return $this->settleTransaction();
    }
}
