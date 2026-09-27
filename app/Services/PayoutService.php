<?php

namespace App\Services;

use App\Helpers\TransactionIdGenerator;
use App\Models\Merchant;
use App\Models\Payout;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    /**
     * Initiate a payout. Always created as PENDING; wallet balance is only
     * ever debited later, when the cron resolves it to SUCCESS (and only
     * if funds are sufficient at that point - see PaymentProcessingService).
     */
    public function initiate(array $data, array $rawPayload = []): Payout
    {
        $merchant = Merchant::findOrFail($data['merchant_id']);

        if (!$merchant->isActive()) {
            throw new \RuntimeException('MERCHANT_INACTIVE');
        }

        $transactionId = TransactionIdGenerator::generate('POT');

        $payout = Payout::create([
            'transaction_id' => $transactionId,
            'merchant_id'    => $merchant->id,
            'amount'         => $data['amount'],
            'currency'       => $data['currency'] ?? 'INR',
            'status'         => Payout::STATUS_PENDING,
            'payload'        => $rawPayload,
            'remarks'        => $data['remarks'] ?? null,
        ]);

        Log::info('Payout initiated', [
            'transaction_id' => $payout->transaction_id,
            'merchant_id'    => $merchant->id,
            'amount'         => $payout->amount,
            'currency'       => $payout->currency,
            'status'         => $payout->status,
        ]);

        return $payout;
    }
}
