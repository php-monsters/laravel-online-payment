<?php
namespace PhpMonsters\Larapay\Adapter;

/**
 * Class Exception
 * @package PhpMonsters\Larapay\Adapter
 */
class Exception extends \PhpMonsters\Larapay\Exception
{
	const UNHANDLED_ERR = 999;

    /**
     * @var string
     */
	protected $adapter = 'larapayadapter';

    /**
     * Exception constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
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
