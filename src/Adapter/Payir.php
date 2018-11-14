<?php
namespace Tartan\Larapay\Adapter;

use Tartan\Larapay\Adapter\Zarinpal\Exception;
use Illuminate\Support\Facades\Log;
use Tartan\Larapay\Pasargad\Helper;

class Payir extends AdapterAbstract implements AdapterInterface
{

	public $endPoint = 'https://pay.ir/payment/send';
	public $endPointForm = 'https://pay.ir/payment/gateway/';
	public $endPointVerify = 'https://pay.ir/payment/verify';

	public $reverseSupport = false;

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
			'api',
			'amount',
			'redirect_url',
			'order_id',
		]);

		$sendParams = [
			'api'  => $this->api,
			'amount'      => intval($this->amount),
			'factorNumber'      => ($this->order_id),
			'description' => $this->description ? $this->description : '',
			'mobile'      => $this->mobile ? $this->mobile : '',
			'redirect' => $this->redirect_url,
		];

		try {



			Log::debug('PaymentRequest call', $sendParams);
            $result = Helper::post2https($sendParams , $this->endPoint);


            $resultobj = json_decode($result);
			Log::info('PaymentRequest response', $this->obj2array($resultobj));


			if (isset($resultobj->status)) {

				if ($resultobj->status == 1) {
					$this->getTransaction()->setReferenceId($resultobj->transId); // update transaction reference id
					return $resultobj->transId;
				}
				else {
					throw new Exception($resultobj->status);
				}
			}
			else {
				throw new Exception('larapay::larapay.invalid_response');
			}
		} catch (\Exception $e) {
			throw new Exception('PayIr Fault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}


	/**
	 * @return mixed
	 */
	protected function generateForm ()
	{
		$authority = $this->requestToken();
		return view('larapay::payir-form', [
			'endPoint'    => $this->endPointForm.$authority,
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("larapay::larapay.goto_gate"),
			'autoSubmit'  => true
		]);
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	protected function verifyTransaction ()
	{
		if($this->getTransaction()->checkForVerify() == false) {
			throw new Exception('larapay::larapay.could_not_verify_payment');
		}

		$this->checkRequiredParameters([
			'api',
			'transId',
		]);

		$sendParams = [
			'api'  => $this->api,
			'transId'   => $this->transId,
		];

		try {

			Log::debug('PaymentVerification call', $sendParams);
            $result = Helper::post2https($sendParams , $this->endPointVerify);
            $response = json_decode($result);
			Log::info('PaymentVerification response', $this->obj2array($response));


			if (isset($response->status, $response->amount)) {

				if($response->status == 1) {
					$this->getTransaction()->setVerified();
					$this->getTransaction()->setReferenceId($this->transId); // update transaction reference id
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
	public function canContinueWithCallbackParameters()
	{

		if (!empty($this->parameters['transId'])) {
			return true;
		}
		return false;
	}

	public function getGatewayReferenceId()
	{
		$this->checkRequiredParameters([
			'transId',
		]);
		return $this->transId;
	}
}
