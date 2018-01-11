<?php
namespace Tartan\Larapay\Facades;
use Illuminate\Support\Facades\Facade;

/**
 * Class Larapay
 * @package Tartan\Larapay\Facades
 * @author Tartan <iamtartan@gmail.com>
 */
class Larapay extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'Tartan\Larapay\Factory';
	}
}