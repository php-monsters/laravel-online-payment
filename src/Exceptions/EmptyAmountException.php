<?php


namespace PhpMonsters\Larapay\Exceptions;

use Exception;

class EmptyAmountException extends Exception
{
    protected $message = 'Amount cannot be empty or zero';
}