<?php
namespace Tartan\Larapay\Adapter;

use SoapClient;
use Tartan\Larapay\Transaction\TransactionInterface;
use Illuminate\Support\Facades\Log;

abstract class AdapterAbstract
{
	protected $endPoint;
	protected $WSDL;

	protected $testWSDL;
	protected $testEndPoint;

	/**
	 * @var array
	 */
	protected $parameters  = [];
	/**
	 * @var array
	 */
	protected $soapOptions = [];

	/**
	 * @var TransactionInterface
	 */
	protected $transaction;

	/**
	 * specifies if gateway supports transaction reverse or not
	 * @var bool
	 */
	protected $reverseSupport = false;

	/**
	 * AdapterAbstract constructor.
	 *
	 * @param TransactionInterface $transaction
	 * @param array $configs
	 *
	 * @throws Exception
	 */
	public function __construct (TransactionInterface $transaction, array $configs = [])
	{
		$this->transaction = $transaction;

		if ($this->transaction->checkForRequestToken() == false) {
			throw new Exception('could not handle this transaction payment');
		}

		$this->setParameters($configs);
		$this->init();
	}

	public function init(){}

	/**
	 * @param string $key
	 * @param mixed $val
	 */
	public function __set ($key, $val)
	{
		$this->parameters[$key] = trim($val);
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function __get ($key)
	{
		return isset($this->parameters[$key]) ? trim($this->parameters[$key]) : null;
	}


	/**
	 * @return TransactionInterface
	 */
	public function getTransaction ()
	{
		return $this->transaction;
	}

	/**
	 * @param array $parameters
	 *
	 * @return $this
	 */
	public function setParameters (array $parameters = [])
	{
		foreach ($parameters as $key => $value) {
			$this->parameters[$key] = trim($value);
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getParameters ()
	{
		return $this->parameters;
	}

	/**
	 * @return string
	 */
	public function form()
	{
		return $this->generateForm();
	}

	/**
	 * @return true
	 */
	public function verify()
	{
		return $this->verifyTransaction();
	}

	/**
	 * @return bool
	 */
	public function afterVerify()
	{
		$this->getTransaction()->setAfterVerified(); // عملیات پیش فرض در صورت عدم نیاز
		return true;
	}

	/**
	 * @return bool
	 */
	public function reverse()
	{
		return $this->reverseTransaction();
	}

	/**
	 * check for required parameters
	 *
	 * @param array $parameters
	 *
	 * @throws Exception
	 */
	protected function checkRequiredParameters (array $parameters)
	{
		foreach ($parameters as $parameter) {
			if (!array_key_exists($parameter, $this->parameters) || trim($this->parameters[$parameter]) == "") {
				throw new Exception("Parameters array must have a not null value for key: '$parameter'");
			}
		}
	}

	/**
	 * @return string
	 */
	protected function getWSDL ()
	{
		if (config('larapay.mode') == 'production') {
			return $this->WSDL;
		} else {
			return $this->testWSDL;
		}
	}

	/**
	 * @return string
	 */
	protected function getEndPoint ()
	{
		if (config('larapay.mode') == 'production') {
			return $this->endPoint;
		} else {
			return $this->testEndPoint;
		}
	}

	/**
	 * @param array $options
     * @deprecated
	 *
	 * 'login'       => config('api.basic.username'),
	 * 'password'    => config('api.basic.password'),
	 * 'proxy_host' => 'localhost',
	 * 'proxy_port' => '8080'
	 *
	 */
	public function setSoapOptions(array $options = [])
	{
        Log::debug('soap options set', $options);
        $this->soapOptions = $options;
	}

    /**
     * @return array
     */
	protected function getSoapOptions()
    {
        return $this->soapOptions;
    }


    /**
	 * @return SoapClient
	 */
	protected function getSoapClient()
	{
//		return new SoapClient($this->getWSDL());
		return new SoapClient($this->getWSDL(), $this->getSoapOptions());
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getGatewayReferenceId()
	{
		throw new Exception(__METHOD__ . ' not implemented');
	}

	/**
	 * @return bool
	 */
	public function reverseSupport()
	{
		return $this->reverseSupport;
	}

	/**
	 * @return bool
	 */
	public function canContinueWithCallbackParameters()
	{
		return true;
	}

	protected function obj2array ($obj)
	{
		$out = [];
		foreach ($obj as $key => $val) {
			switch (true) {
				case is_object($val):
					$out[$key] = $this->obj2array($val);
					break;
				case is_array($val):
					$out[$key] = $this->obj2array($val);
					break;
				default:
					$out[$key] = $val;
			}
		}

		return $out;
	}
}
