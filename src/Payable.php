<?php

namespace Tartan\Larapay;

use Tartan\Larapay\Contracts\LarapayTransaction as LarapayTransactionContract;


trait Payable
{
    public function transactions()
    {
        return $this->morphMany(app(LarapayTransactionContract::class), 'model');
    }

    public function deleteTransaction($larapayTransactionId)
    {

    }

    public function startTransaction(
        $paymentGateway,
        $amount = null,
        $description = null,
        $callback = null,
        array $adapterConfig = []
    ) {

        $larapayTransaction = [];
        if ($amount !== null) {
            $larapayTransaction['amount'] = $amount;
        } else {
            $larapayTransaction['amount'] = $this->getAmount();
        }

        $larapayTransaction['description'] = $description;
        $larapayTransaction['gate_name'] = $paymentGateway;
        $larapayTransaction['submitted'] = true;
        $larapayTransaction['bank_order_id'] = $this->generateBankOrderId($paymentGateway);
        $larapayTransaction['payment_method'] = 'ONLINE';


      //  $this->transactions()->save($larapayTransaction);

        $this->transactions()->create($larapayTransaction);

        $paymentGatewayHandler = Larapay::make($paymentGateway, $larapayTransaction);

        $paymentParams = [
            'order_id'     => $larapayTransaction->getBankOrderId(),
            'redirect_url' => route(config("larapay.callback"), [
                'gateway'       => $paymentGateway,
                'transactionId' => $larapayTransaction->id,
            ]),
            'amount'       => $larapayTransaction->amount,
            'submit_label' => trans('larapay::larapay.goto_gate'),
        ];


        try {

            $form = $paymentGatewayHandler->form($paymentParams);

            return $form;

        } catch (\Exception $e) {

            Log::emergency($paymentGateway . ' #' . $e->getCode() . '-' . $e->getMessage());
            Session::flash('alert-danger',
                trans('trans.could_not_create_goto_bank_form', ['gateway' => $paymentGateway]));

            return redirect()->back()->withInput();
        }

        if (is_null($form)) {
            return redirect()->back()->withInput();
        }


    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function generateBankOrderId (string $bank): int
    {
        // handle each gateway exception
        switch ($bank) {
            default: {
                return time() . mt_rand(10, 99);
            }
        }
    }
}