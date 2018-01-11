<?php
namespace Tartan\Larapay\Transaction;

interface TransactionInterface
{
	public function setReferenceId($referenceId, $save = true);

	public function checkForRequestToken();

	public function checkForVerify();

	public function checkForInquiry();

	public function checkForReverse();

	public function checkForAfterVerify();

	public function setCardNumber($cardNumber);

	public function setVerified();

	public function setAfterVerified();

	public function setSuccessful($flag);

	public function setReversed();

	public function getAmount();

	public function setPaidAt($time = 'now');

	public function setExtra($key, $value, $save = false);
}