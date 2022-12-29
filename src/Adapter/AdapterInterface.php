<?php
namespace PhpMonsters\Larapay\Adapter;

interface AdapterInterface
{
    /**
     * @param array $parameters
     *
     * @return AdapterInterface
     */
	public function setParameters(array $parameters = []): AdapterInterface;

    /**
     * @return string
     */
    public function form(): string;

    /**
     * @return array
     */
    public function formParams(): array;

    /**
     * @return bool
     */
    public function verify(): bool;

	/**
	 * for handling after verify methods like settle in Mellat gateway
     *
	 * @return mixed
	 */
    public function afterVerify(): bool;

    /**
     * @return bool
     */
    public function reverse(): bool;

    /**
     * @return string
     */
    public function getGatewayReferenceId(): string;

    /**
     * @return bool
     */
    public function canContinueWithCallbackParameters(): bool;
}
