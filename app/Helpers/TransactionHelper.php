<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class TransactionHelper
{
    /**
     * Generate a unique, human-readable transaction id.
     * Format: PIN-YYYYMMDD-XXXXXXXX  (or POUT-... for payouts)
     */
    public static function generate(string $prefix): string
    {
        do {
            $candidate = sprintf(
                '%s-%s-%s',
                strtoupper($prefix),
                now()->format('Ymd'),
                strtoupper(Str::random(8))
            );
        } while (self::alreadyExists($candidate));

        return $candidate;
    }

    /**
     * Checks both payins and payouts tables since transaction ids
     * are expected to be globally unique across the platform.
     */
    protected static function alreadyExists(string $transactionId): bool
    {
        return \App\Models\Payin::where('transaction_id', $transactionId)->exists()
            || \App\Models\Payout::where('transaction_id', $transactionId)->exists();
    }
}
