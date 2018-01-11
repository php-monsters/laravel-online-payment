<?php
namespace Tartan\Larapay\Adapter;

use SoapClient;
use SoapFault;
use Tartan\Larapay\Adapter\Parsian\Exception;
use Illuminate\Support\Facades\Log;

class Parsian extends AdapterAbstract implements AdapterInterface
{
	protected $WSDL = 'https://pec.shaparak.ir/pecpaymentgateway/eshopservice.asmx?WSDL';
	protected $endPoint = 'https://pec.shaparak.ir/pecpaymentgateway';

	protected $testWSDL = 'http://banktest.ir/gateway/parsian/ws?wsdl';
	protected $testEndPoint = 'http://banktest.ir/gateway/parsian/gate';

	protected $reverseSupport = true;

	/**
	 * @return array
	 * @throws Exception
	 */
	protected function requestToken ()
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
			'pin'         => $this->pin,
			'amount'      => intval($this->amount),
			'orderId'     => $this->order_id,
			'callbackUrl' => $this->redirect_url,
			'authority'   => 0, //default authority
			'status'      => 1, //default status
		];

		try {
            $soapClient = $this->getSoapClient();

			Log::debug('PinPaymentRequest call', $sendParams);

			$response = $soapClient->__soapCall('PinPaymentRequest', $sendParams);

			Log::debug('PinPaymentRequest response', $this->obj2array($response));

			if (isset($response->status, $response->authority)) {
				if ($response->status == 0) {
					$this->getTransaction()->setReferenceId($response->authority); // update transaction reference id
					return $response->authority;
				}
				else {
					throw new Exception($this->status);
				}
			}
			else {
				throw new Exception('larapay::parsian.errors.invalid_response');
			}
		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}

	/**
	 * @return mixed
	 */
	protected function generateForm ()
	{
		$authority = $this->requestToken();

		return view('larapay::parsian-form', [
			'endPoint'    => $this->getEndPoint(),
			'refId'       => $authority,
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
			'autoSubmit'  => boolval($this->auto_submit)
		]);
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	protected function verifyTransaction ()
	{
		if ($this->getTransaction()->checkForVerify() == false) {
			throw new Exception('larapay::larapay.could_not_verify_payment');
		}

		$this->checkRequiredParameters([
			'pin',
			'au',
			'rs'
		]);

		if ($this->rs !== '0') {
			throw new Exception('larapay::parsian.errors.could_not_continue_with_non0_rs');
		}

		$sendParams = [
			'pin'       => $this->pin,
			'authority' => $this->au,
			'status'    => 1
		];

		try {
            $soapClient = $this->getSoapClient();
			$sendParams = array(
				'pin'       => $this->pin,
				'authority' => $this->au,
				'status'    => 1
			);

			Log::debug('PinPaymentEnquiry call', $sendParams);

			$response   = $soapClient->__soapCall('PinPaymentEnquiry', $sendParams);

			Log::debug('PinPaymentEnquiry response', $this->obj2array($response));

			if (isset($response->status)) {
				if ($response->status == 0) {
					$this->getTransaction()->setVerified();
					return true;
				}
				else {
					throw new Exception($response->status);
				}
			}
			else {
				throw new Exception('larapay::parsian.errors.invalid_response');
			}

		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}


	/**
	 * @return bool
	 * @throws Exception
	 */
	protected function reverseTransaction ()
	{
		if ($this->reverseSupport == false || $this->getTransaction()->checkForReverse() == false) {
			throw new Exception('larapay::larapay.could_not_reverse_payment');
		}

		$this->checkRequiredParameters([
			'pin',
			'order_id',
			'reverse_order_id',
		]);

		$sendParams = [
			'pin'             => $this->pin,
			'orderId'         => $this->reverse_order_id,
			'orderToReversal' => $this->order_id,
			'status'          => 1,
		];

		try {
			$soapClient = $this->getSoapClient();
			Log::debug('PinReversal call', $sendParams);

			$response   = $soapClient->__soapCall('PinReversal', $sendParams);

			Log::debug('PinReversal response', $this->obj2array($response));

			if (isset($response->status)) {
				if ($response->status == 0) {
					$this->getTransaction()->setReversed();

					return true;
				}
				else {
					throw new Exception($response->status);
				}
			}
			else {
				throw new Exception('larapay::parsian.errors.invalid_response');
			}
		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}

	public function getGatewayReferenceId()
	{
		$this->checkRequiredParameters([
			'au',
		]);
		return $this->au;
	}
}
