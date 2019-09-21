<?php

namespace Tartan\Larapay;

use Tartan\Larapay\Models\LarapayTransaction;

trait Payable
{
    public function transactions()
    {
        return $this->morphMany(LarapayTransaction::class, 'model');
    }
}