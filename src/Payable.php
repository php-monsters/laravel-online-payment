<?php

namespace Tartan\Larapay;

use Illuminate\Support\Facades\Log;
use Tartan\Larapay\Contracts\LarapayTransaction as LarapayTransactionContract;
use Tartan\Larapay\Exceptions\EmptyAmountException;
use Tartan\Larapay\Facades\Larapay;
use Exception;

trait Payable
{
    public function transactions()
    {
        return $this->morphMany(app(LarapayTransactionContract::class), 'model');
    }

    public function cerateTransaction(
        $paymentGateway,
        $amount = null,
        $description = null,
        array $adapterConfig = []
    ) {

        $transactionData = [];

        $transactionData['amount'] = $amount;
        if ($amount == null) {
            $transactionData['amount'] = $this->getAmount();
        }

        if ($transactionData['amount'] == null || $transactionData['amount'] == 0) {
            throw new EmptyAmountException();
        }

        $paymentGateway = ucfirst(strtolower($paymentGateway));

        $transactionData['description'] = $description;
        $transactionData['gate_name'] = $paymentGateway;
        $transactionData['submitted'] = true;
        $transactionData['bank_order_id'] = $this->generateBankOrderId($paymentGateway);
        $transactionData['payment_method'] = 'ONLINE';
        $transactionData['gateway_properties'] = json_encode($adapterConfig, JSON_UNESCAPED_UNICODE);

        $transaction = $this->transactions()->create($transactionData);

        return $transaction;

    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function generateBankOrderId(string $bank = null): int
    {
        // handle each gateway exception
        switch ($bank) {
            default:
            {
                return time() . mt_rand(10, 99);
            }
        }
    }
}