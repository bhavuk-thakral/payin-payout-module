<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class TransactionIdGenerator
{
    /**
     * Generate a unique transaction id like PIN20260925ABCD1234 / POT20260925ABCD1234
     */
    public static function generate(string $prefix): string
    {
        do {
            $id = strtoupper($prefix) . now()->format('Ymd') . strtoupper(Str::random(10));
        } while (self::exists($id, $prefix));

        return $id;
    }

    protected static function exists(string $id, string $prefix): bool
    {
        // PIN -> pay_ins, POT -> payouts
        return match ($prefix) {
            'PIN' => \App\Models\PayIn::where('transaction_id', $id)->exists(),
            'POT' => \App\Models\Payout::where('transaction_id', $id)->exists(),
            default => false,
        };
    }
}
