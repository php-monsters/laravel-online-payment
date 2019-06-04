<?php
namespace Tartan\Larapay\Adapter\Pasargad;

/**
 * Class RSAProcessor
 * @package Tartan\Larapay\Adapter\Pasargad
 */
class RSAProcessor
{
	private $public_key = null;
	private $private_key = null;
	private $modulus = null;
	private $key_length = "1024";

    /**
     * RSAProcessor constructor.
     *
     * @param null $xmlRsaKey
     * @param null $type
     */
	public function __construct ($xmlRsaKey = null, $type = null)
	{
		$xmlObj = null;
		if ($xmlRsaKey == null) {
			$xmlObj = simplexml_load_file("xmlfile/RSAKey.xml");
		}
		elseif ($type == RSAKeyType::XMLFile) {
			$xmlObj = simplexml_load_file($xmlRsaKey);
		}
		else {
			$xmlObj = simplexml_load_string($xmlRsaKey);
		}
		$this->modulus     = RSA::binary_to_number(base64_decode($xmlObj->Modulus));
		$this->public_key  = RSA::binary_to_number(base64_decode($xmlObj->Exponent));
		$this->private_key = RSA::binary_to_number(base64_decode($xmlObj->D));
		$this->key_length  = strlen(base64_decode($xmlObj->Modulus)) * 8;
	}

	public function getPublicKey ()
	{
		return $this->public_key;
	}

	public function getPrivateKey ()
	{
		return $this->private_key;
	}

	public function getKeyLength (): int
	{
		return $this->key_length;
	}

	public function getModulus ()
	{
		return $this->modulus;
	}

	public function encrypt ($data)
	{
		return base64_encode(RSA::rsa_encrypt($data, $this->public_key, $this->modulus, $this->key_length));
	}

	public function dencrypt ($data)
	{
		return RSA::rsa_decrypt($data, $this->private_key, $this->modulus, $this->key_length);
	}

	public function sign ($data)
	{
		return RSA::rsa_sign($data, $this->private_key, $this->modulus, $this->key_length);
	}

	public function verify ($data)
	{
		return RSA::rsa_verify($data, $this->public_key, $this->modulus, $this->key_length);
	}
}

class RSAKeyType
{
	const XMLFile = 0;
	const XMLString = 1;
}
