<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $fillable = [
        'transaction_id',
        'type',
        'event',
        'status',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];
}
