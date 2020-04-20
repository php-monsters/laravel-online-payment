<?php

namespace Tartan\Larapay;

use Tartan\Log\Facades\XLog;
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

    public function accomplishedTransactions()
    {
        return $this->morphMany(app(LarapayTransactionContract::class), 'model')->where('accomplished', true);
    }

    public function isPaid()
    {
        $accomplishedTransactions = $this->accomplishedTransactions;
        if ($accomplishedTransactions->count() != 0) {
            return true;
        }

        return false;
    }

    public function paidAmount()
    {
        $accomplishedTransactions = $this->accomplishedTransactions;
        $amount = 0;
        foreach ($accomplishedTransactions as $accomplishedTransaction) {
            $amount += $accomplishedTransaction->amount;
        }

        return $amount;
    }

    public function createTransaction(
        $paymentGateway,
        $amount = null,
        $description = null,
        array $additionalData = []
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
        $transactionData['additional_data'] = json_encode($additionalData, JSON_UNESCAPED_UNICODE);

        return  $this->transactions()->create($transactionData);
    }

    public function getAmount()
    {
        return intval($this->amount) * 10;
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
