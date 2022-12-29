<?php
namespace PhpMonsters\Larapay\Facades;
use Illuminate\Support\Facades\Facade;

/**
 * Class Larapay
 * @package PhpMonsters\Larapay\Facades
 *
 */
class Larapay extends Facade
{
    /**
     * @return string
     */
	protected static function getFacadeAccessor()
	{
		return 'larapay';
	}
}
