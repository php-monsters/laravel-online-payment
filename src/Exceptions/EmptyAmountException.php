<?php


namespace Tartan\Larapay\Exceptions;

use Exception;

class EmptyAmountException extends Exception
{
    protected $message = 'Amount cannot be empty or zero';
}