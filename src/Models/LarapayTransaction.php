<?php


namespace Tartan\Larapay\Models;


use Illuminate\Database\Eloquent\Model;
use Tartan\Larapay\Models\Traits\OnlineTransactionTrait;
use Tartan\Larapay\Transaction\TransactionInterface;
use Illuminate\Database\Eloquent\SoftDeletes;


class LarapayTransaction extends Model implements TransactionInterface
{
    use SoftDeletes;
    use OnlineTransactionTrait;

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