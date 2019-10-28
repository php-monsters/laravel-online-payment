<?php

namespace Tartan\Larapay;

use Illuminate\Support\Facades\Log;
use Tartan\Larapay\Contracts\LarapayTransaction as LarapayTransactionContract;
use Tartan\Larapay\Exceptions\EmptyAmountException;
use Tartan\Larapay\Facades\Larapay;
use Exception;
use Tartan\Larapay\Models\LarapayTransaction;

trait Payable
{
    public function transactions()
    {
        return $this->morphMany(app(LarapayTransactionContract::class), 'model');
    }

    public function deleteTransaction($transactionId, $force = false)
    {
        $transaction = LarapayTransaction::find($transactionId);

        if($force){
            $transaction->forceDelete();
        } else{
            $transaction->delete();
        }
    }

    public function startTransaction(
        $paymentGateway,
        $amount = null,
        $description = null,
        $callback = null,
        array $adapterConfig = []
    ) {

        $transactionData = [];

        $transactionData['amount'] = $amount;
        if($amount == null){
            $transactionData['amount'] = $this->getAmount();
        }

        if($transactionData['amount'] == null || $transactionData['amount'] == 0){
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
        $paymentGatewayHandler = Larapay::make($paymentGateway, $transaction, $adapterConfig);

        $callbackRoute = route(config("larapay.payment_callback"), [
            'gateway' => $paymentGateway,
            'transactionId' => $transaction->id,
        ]);

        if($callback != null){
            $callbackRoute = route($callback , [
                'gateway' => $paymentGateway,
                'transactionId' => $transaction->id,
            ]);
        }

        $paymentParams = [
            'order_id' => $transaction->getBankOrderId(),
            'redirect_url' => $callbackRoute,
            'amount' => $transaction->amount,
            'submit_label' => trans('larapay::larapay.goto_gate'),
        ];

        try {
            $form = $paymentGatewayHandler->form($paymentParams);

            return $form;
        } catch (Exception $e) {
            Log::emergency($paymentGateway . ' #' . $e->getCode() . '-' . $e->getMessage());
            return false;
        }

    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function generateBankOrderId(string $bank): int
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