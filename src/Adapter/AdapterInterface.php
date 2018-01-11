<?php
namespace Tartan\Larapay\Adapter;

interface AdapterInterface
{
	public function setParameters(array $parameters = []);

    public function form();

    public function verify();

	/**
	 * for handling after verify methods like settle in Mellat gateway
	 * @return mixed
	 */
    public function afterVerify();

    public function reverse();

    public function getGatewayReferenceId();

    public function canContinueWithCallbackParameters();
}
