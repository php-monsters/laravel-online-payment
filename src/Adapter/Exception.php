<?php
namespace Tartan\Larapay\Adapter;

class Exception extends \Tartan\Larapay\Exception
{
	const UNHANDLED_ERR = 999;

	protected $adapter = 'larapayadapter';

	public function __construct($message = "", $code = 0, Exception $previous = null)
	{
		$gate = explode('\\', $this->adapter );
		$gate = end($gate);
		$gate = strtolower($gate);

		switch ($message)
		{
			case is_numeric($message): {
				$code = $message;
				$message = 'larapay::larapay.'.$gate.'.errors.error_' . str_replace('-', '_', strval($message)); // fetch message from translation file
				break;
			}

			case preg_match('/^larapay::/', $message) == 1 : {
				$code = static::UNHANDLED_ERR;
				$message = trans(strval($message)); // fetch message from translation file
				break;
			}
		}

		parent::__construct($message, $code, $previous);
	}
}