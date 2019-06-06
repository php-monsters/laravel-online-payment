<?php

namespace Tartan\Larapay\Adapter;

use SoapClient;
use Tartan\Larapay\Transaction\TransactionInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class AdapterAbstract
 * @package Tartan\Larapay\Adapter
 */
abstract class AdapterAbstract
{
    /**
     * @var string
     */
    protected $endPoint;

    /**
     * @var string
     */
    protected $WSDL;

    /**
     * @var string
     */
    protected $testWSDL;

    /**
     * @var
     */
    protected $testEndPoint;

    /**
     * @var array
     */
    protected $parameters = [];

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
    public function __construct(TransactionInterface $transaction, array $configs = [])
    {
        $this->transaction = $transaction;

        if ($this->transaction->checkForRequestToken() == false) {
            throw new Exception('Due to checkForRequestToken() result, could not handle this transaction`s payment process');
        }

        $this->setParameters($configs);
        $this->init();
    }

    /**
     * Adapter`s init method that called after construct method
     */
    public function init() { }

    /**
     * @param string $key
     * @param mixed $val
     */
    public function __set($key, $val)
    {
        $this->parameters[$key] = trim($val);
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function __get($key)
    {
        return isset($this->parameters[$key]) ? trim($this->parameters[$key]) : null;
    }


    /**
     * @return TransactionInterface
     */
    public function getTransaction(): TransactionInterface
    {
        return $this->transaction;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function setParameters(array $parameters = []): AdapterInterface
    {
        foreach ($parameters as $key => $value) {
            $this->parameters[$key] = trim($value);
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getParameter($key)
    {
        return isset($this->parameters[$key]) ? trim($this->parameters[$key]) : null;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function form(): string
    {
        return $this->generateForm();
    }

    /**
     * @return true
     */
    public function verify(): bool
    {
        return $this->verifyTransaction();
    }

    /**
     * @return bool
     */
    public function afterVerify(): bool
    {
        $this->getTransaction()->setAfterVerified(); // عملیات پیش فرض در صورت عدم نیاز

        return true;
    }

    /**
     * @return bool
     */
    public function reverse(): bool
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
    protected function checkRequiredParameters(array $parameters)
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
    protected function getWSDL()
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
    protected function getEndPoint()
    {
        if (config('larapay.mode') == 'production') {
            return $this->endPoint;
        } else {
            return $this->testEndPoint;
        }
    }

    /**
     * @param array $options
     *
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
     * @throws \SoapFault
     */
    protected function getSoapClient(): SoapClient
    {
        return new SoapClient($this->getWSDL(), $this->getSoapOptions());
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getGatewayReferenceId(): string
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
    public function canContinueWithCallbackParameters(): bool
    {
        return true;
    }

    /**
     * @param $obj
     *
     * @return array
     */
    protected function obj2array($obj)
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
