<?php
namespace Tartan\Larapay;

use Tartan\Larapay\Adapter\AdapterInterface;
use Tartan\Larapay\Transaction\TransactionInterface;
use Illuminate\Support\Facades\Log;

class Factory
{
	/**
	 * @var AdapterInterface
	 */
	protected $gateway;

    /**
     * @param $adapter adapter name
     * @param TransactionInterface $invoice
     * @param array adapter configuration
     *
     * @return $this
     * @throws Exception
     */
	public function make($adapter, TransactionInterface $invoice, array $adapterConfig = [])
	{
		$adapter = ucfirst(strtolower($adapter));

		/**
		 *  check for supported gateways
		 */
		$readyToServerGateways = explode(',', config('larapay.gateways'));

		Log::debug('selected gateway [' . $adapter .']');
		Log::debug('available gateways', $readyToServerGateways);

		if (!in_array($adapter, $readyToServerGateways)) {
			throw new Exception(trans('larapay::larapay.gate_not_ready'));
		}

		$adapterNamespace = 'Tartan\Larapay\Adapter\\';
		$adapterName  = $adapterNamespace . $adapter;

		if (!class_exists($adapterName)) {
			throw new Exception("Adapter class '$adapterName' does not exist");
		}

		$config = count($adapterConfig) ? $adapterConfig : config('larapay.'.strtolower($adapter));
		Log::debug('init gateway config', $config);

		$bankAdapter = new $adapterName($invoice, $config);

		if (!$bankAdapter instanceof AdapterInterface) {
			throw new Exception(trans('larapay::larapay.gate_not_ready'));
		}

		// setting soapClient options if required
		if (config('larapay.soap.useOptions') == true) {
            $bankAdapter->setSoapOptions(config('larapay.soap.options'));
        }

		$this->gateway = $bankAdapter;

		return $this;
	}

	public function __call ($name, $arguments)
	{
		if (empty($this->gateway)) {
			throw new Exception("Gateway not defined before! please use make method to initialize gateway");
		}

		Log::info($name, $arguments);

		// چو ن همیشه متد ها با یک پارامتر کلی بصورت آرایه فراخوانی میشوند. مثلا:
		// $paymentGatewayHandler->generateForm($ArrayOfExtraPaymentParams)
		if (count($arguments) > 0) {
			$this->gateway->setParameters($arguments[0]); // set parameters
		}

		try {
			return call_user_func_array([$this->gateway, $name], $arguments); // call desire method
		} catch (\Exception $e) {
			Log::error($e->getMessage() .' #'.$e->getCode(). ' File:'.$e->getFile().':'.$e->getLine());
			throw $e;
		}
	}
}
