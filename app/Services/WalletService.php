<?php

namespace App\Services;

use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * Credit a merchant's wallet (used when a pay-in succeeds).
     * Locks the wallet row so concurrent cron workers can't double-credit.
     */
    public function credit(int $merchantId, float $amount, string $reason = ''): Wallet
    {
        return DB::transaction(function () use ($merchantId, $amount, $reason) {
            $wallet = Wallet::where('merchant_id', $merchantId)->lockForUpdate()->firstOrFail();

            $wallet->balance = bcadd((string) $wallet->balance, (string) $amount, 2);
            $wallet->save();

            Log::channel('single')->info('Wallet credited', [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'new_balance' => $wallet->balance,
                'reason' => $reason,
            ]);

            return $wallet;
        });
    }

    /**
     * Debit a merchant's wallet (used when a payout is initiated / reserved).
     * Throws if the merchant doesn't have sufficient balance.
     */
    public function debit(int $merchantId, float $amount, string $reason = ''): Wallet
    {
        return DB::transaction(function () use ($merchantId, $amount, $reason) {
            $wallet = Wallet::where('merchant_id', $merchantId)->lockForUpdate()->firstOrFail();

            if (bccomp((string) $wallet->balance, (string) $amount, 2) < 0) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $wallet->balance = bcsub((string) $wallet->balance, (string) $amount, 2);
            $wallet->save();

            Log::channel('single')->info('Wallet debited', [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'new_balance' => $wallet->balance,
                'reason' => $reason,
            ]);

            return $wallet;
        });
    }

    /**
     * Reverse a previous debit (used when a payout ultimately fails).
     */
    public function reverse(int $merchantId, float $amount, string $reason = ''): Wallet
    {
        return $this->credit($merchantId, $amount, 'reversal: '.$reason);
    }
}
