<?php
declare(strict_types=1);

namespace PhpMonsters\Larapay\Transaction;

interface TransactionInterface
{
    /**
     * set gateway token of transaction
     *
     * @param string $token
     * @param bool $save
     *
     * @return mixed
     */
    public function setGatewayToken(string $token, bool $save = true): bool;

    /**
     * set reference ID of transaction
     *
     * @param string $referenceId
     * @param bool $save
     *
     * @return mixed
     */
    public function setReferenceId(string $referenceId, bool $save = true): bool;

    /**
     * check if transaction is ready for requesting token from payment gateway or not
     *
     * @return boolean
     */
    public function checkForRequestToken(): bool;

    /**
     * check if transaction is ready for requesting verify method from payment gateway or not
     *
     * @return bool
     */
    public function checkForVerify(): bool;

    /**
     * check if transaction is ready for requesting inquiry method from payment gateway or not
     * This feature does not append to all payment gateways
     *
     * @return bool
     */
    public function checkForInquiry(): bool;

    /**
     * check if transaction is ready for requesting after verify method from payment gateway or not
     * This feature does not append to all payment gateways.
     * for example in Mellat gateway this method can assume as SETTLE method
     *
     * @return bool
     */
    public function checkForAfterVerify(): bool;

    /**
     * check if transaction is ready for requesting refund method from payment gateway or not
     * This feature does not append to all payment gateways
     *
     * @return bool
     */
    public function checkForReverse(): bool;

    /**
     * Set the card number (hash of card number) that used for paying the transaction
     * This data does not provide by all payment gateways
     *
     * @param string $cardNumber
     * @param bool $save
     *
     * @return bool
     */
    public function setCardNumber(string $cardNumber, bool $save = true): bool;

    /**
     * Mark transaction as a verified transaction
     *
     * @param bool $save
     *
     * @return bool
     */
    public function setVerified(bool $save = true): bool;

    /**
     * Mark transaction as a after verified transaction
     * For example SETTLED in Mellat gateway
     *
     * @param bool $save
     *
     * @return bool
     */
    public function setAfterVerified(bool $save = true): bool;

    /**
     * Mark transaction as a paid/successful transaction
     *
     * @param bool $save
     *
     * @return bool
     */
    public function setAccomplished(bool $save = true): bool;

    /**
     * Mark transaction as a refunded transaction
     *
     * @param bool $save
     *
     * @return bool
     */
	public function setRefunded(bool $save = true): bool;

	/**
     * Returns the payable amount af the transaction
     *
     * @return int
     */
	public function getPayableAmount(): int;

    /**
     * Set callback parameters from payment gateway
     *
     * @param array $parameters
     * @param bool $save
     *
     * @return bool
     */
    public function setCallBackParameters(array $parameters, bool $save = true): bool;

	/**
     * Set extra values of the transaction. Every key/value pair that you want to bind to the transaction
     *
     * @param string $key
     * @param $value
     * @param bool $save
     *
     * @return bool
     */
	public function setExtra(string $key, $value, bool $save = true): bool;
}
