<?php

namespace App\Services;

use App\Helpers\TransactionIdGenerator;
use App\Models\Merchant;
use App\Models\PayIn;
use Illuminate\Support\Facades\Log;

class PayInService
{
    /**
     * Initiate a pay-in. Always created as PENDING; the cron/console
     * command is what later resolves it to SUCCESS/FAILED.
     */
    public function initiate(array $data, array $rawPayload = []): PayIn
    {
        $merchant = Merchant::findOrFail($data['merchant_id']);

        if (!$merchant->isActive()) {
            throw new \RuntimeException('MERCHANT_INACTIVE');
        }

        $transactionId = TransactionIdGenerator::generate('PIN');

        $payIn = PayIn::create([
            'transaction_id' => $transactionId,
            'merchant_id'    => $merchant->id,
            'amount'         => $data['amount'],
            'currency'       => $data['currency'] ?? 'INR',
            'status'         => PayIn::STATUS_PENDING,
            'payload'        => $rawPayload,
            'remarks'        => $data['remarks'] ?? null,
        ]);

        Log::info('Pay-in initiated', [
            'transaction_id' => $payIn->transaction_id,
            'merchant_id'    => $merchant->id,
            'amount'         => $payIn->amount,
            'currency'       => $payIn->currency,
            'status'         => $payIn->status,
        ]);

        return $payIn;
    }
}
