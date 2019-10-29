<?php

namespace Tartan\Larapay\Models;

use Illuminate\Database\Eloquent\Model;
use Tartan\Larapay\Exceptions\FailedReverseTransactionException;
use Tartan\Larapay\Models\Traits\OnlineTransactionTrait;
use Tartan\Larapay\Transaction\TransactionInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tartan\Log\Facades\XLog;
use Exception;

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

    public function model()
    {
        return $this->morphTo();
    }

    public function reverseTransaction()
    {
        //make payment gateway handler
        $gatewayProperties = json_decode($this->gateway_properties, true);
        $paymentGatewayHandler = $this->make($this->gate_name, $this, $gatewayProperties);
        //get reference id
        $referenceId = $paymentGatewayHandler->getGatewayReferenceId();
        //try 3 times to reverse transaction
        $reversed = false;
        for ($i = 1; $i <= 3; $i++) {
            try {
                $reverseResult = $paymentGatewayHandler->reverse();
                if ($reverseResult) {
                    $reversed = true;
                }

                break;
            } catch (Exception $e) {
                XLog::error('Exception: ' . $e->getMessage(), ['try' => $i, 'tag' => $referenceId]);
                continue;
            }
        }
        //throw exception when 3 times failed
        if ($reversed !== true) {
            XLog::error('invoice reverse failed', ['tag' => $referenceId]);
            throw new FailedReverseTransactionException(trans('larapay::larapay.reversed_failed'));
        }
        //log true result
        XLog::info('invoice reversed successfully', ['tag' => $referenceId]);

        return true;
    }

}