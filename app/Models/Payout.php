<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    use CrudTrait;
    protected $fillable = [
        'transaction_id',
        'merchant_id',
        'amount',
        'currency',
        'status',
        'beneficiary_name',
        'beneficiary_account',
        'ifsc_code',
        'meta',
        'processed_at',
        'wallet_debited',
        'wallet_reversed',
        'attempts',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'processed_at' => 'datetime',
        'wallet_debited' => 'boolean',
        'wallet_reversed' => 'boolean',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }
}
