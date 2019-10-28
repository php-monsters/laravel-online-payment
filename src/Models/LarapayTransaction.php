<?php

namespace Tartan\Larapay\Models;

use Illuminate\Database\Eloquent\Model;
use Tartan\Larapay\Exceptions\FailedReverseTransactionException;
use Tartan\Larapay\Models\Traits\OnlineTransactionTrait;
use Tartan\Larapay\Transaction\TransactionInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tartan\Log\Facades\XLog;

class LarapayTransaction extends Model implements TransactionInterface
{
    use SoftDeletes;
    use OnlineTransactionTrait;

    protected $table = 'larapay_transactions';

    protected $fillable = [
        'gate_name',
        'amount',
        'bank_order_id',
        'gate_refid',
        'gate_status',
        'paid_at',
        'jalali_paid_at',
        'verified',
        'after_verified',
        'reversed',
        'submitted',
        'approved',
        'rejected',
        'description',
        'extra_params',
        'model',
        'gateway_properties',
    ];

    protected $attributes = [

    ];

    protected $hidden = [

    ];

    protected $casts = [

    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'paid_at',
    ];

    public function generateBankOrderId (string $bank = null): int
    {
        // handle each gateway exception
        switch ($bank) {
            default: {
                return time() . mt_rand(10, 99);
            }
        }
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function generateBankForm($callback = null)
    {

    }

    public function reverseTransaction()
    {

        $paymentGatewayHandler = $this->make($this->gate_name, $this);
        // گرفتن Reference Number از پارامترهای دریافتی از درگاه پرداخت
        $referenceId = $paymentGatewayHandler->getGatewayReferenceId();
        // reverse start ---------------------------------------------------------------------------------
        // سه بار تلاش برای برگشت زدن تراکنش
        $reversed = false;
        for ($i = 1; $i <= 3; $i++) {
            try {
                $reverseResult = $paymentGatewayHandler->reverse();
                if ($reverseResult) {
                    $reversed = true;
                }

                break;
            } catch (Exception $e) {
                XLog::error('Exception: ' . $e->getMessage(), ['try' => $i, 'tag' => $referenceId ]);
                continue;
            }
        }

        if ($reversed !== true) {
            XLog::error('invoice reverse failed', ['tag' => $referenceId]);
            throw new FailedReverseTransactionException(trans('gate.transaction_reversed_failed'));
        } else {
            XLog::info('invoice reversed successfully', ['tag' => $referenceId]);
        }
    }

}