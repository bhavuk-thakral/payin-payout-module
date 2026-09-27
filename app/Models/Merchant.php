<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Merchant extends Model
{
    use HasFactory;
    use CrudTrait;

    protected $fillable = [
        'merchant_code',
        'name',
        'email',
        'phone',
        'api_key',
        'status',
    ];

    protected static function booted(): void
    {
        // auto-generate merchant_code and api_key on creation if not supplied
        static::creating(function (Merchant $merchant) {
            $merchant->merchant_code = $merchant->merchant_code ?: 'MERCH-'.strtoupper(Str::random(8));
            $merchant->api_key = $merchant->api_key ?: Str::random(40);
        });

        // every merchant gets a wallet automatically
        static::created(function (Merchant $merchant) {
            $merchant->wallet()->firstOrCreate([], ['balance' => 0, 'currency' => 'INR']);
        });
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function payins()
    {
        return $this->hasMany(Payin::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
