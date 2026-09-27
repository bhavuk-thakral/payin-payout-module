<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payin extends Model
{
    use HasFactory;
    use CrudTrait;

    protected $fillable = [
        'transaction_id',
        'merchant_id',
        'amount',
        'currency',
        'status',
        'customer_name',
        'customer_email',
        'payment_method',
        'meta',
        'processed_at',
        'wallet_credited',
        'attempts',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'processed_at' => 'datetime',
        'wallet_credited' => 'boolean',
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
