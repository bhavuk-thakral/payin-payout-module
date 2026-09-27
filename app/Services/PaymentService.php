<?php

namespace App\Services;

use App\Helpers\TransactionHelper;
use App\Models\Merchant;
use App\Models\Payin;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;

/**
 * Handles initiation of pay-ins and payouts.
 * Controllers should stay thin and simply delegate to this service.
 */
class PaymentService
{
    public function __construct(
        protected WalletService $walletService,
        protected PaymentLogService $logger,
    ) {
    }

    /**
     * Initiate a pay-in. Always created with status PENDING.
     * The wallet is only touched later by the cron, on SUCCESS.
     */
    public function initiatePayin(Merchant $merchant, array $data): Payin
    {
        $transactionId = TransactionHelper::generate('PIN');

        $payin = Payin::create([
            'transaction_id' => $transactionId,
            'merchant_id' => $merchant->id,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'INR',
            'status' => 'PENDING',
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        $this->logger->log($transactionId, 'payin', 'initiated', 'PENDING', [
            'merchant_id' => $merchant->id,
            'amount' => $payin->amount,
            'request' => $data,
        ]);

        return $payin;
    }

    /**
     * Initiate a payout. Funds are reserved (debited) immediately so the
     * same balance cannot be used twice while the payout is still pending.
     * If the payout later fails, the cron reverses this debit.
     */
    public function initiatePayout(Merchant $merchant, array $data): Payout
    {
        return DB::transaction(function () use ($merchant, $data) {
            $transactionId = TransactionHelper::generate('POUT');

            // reserve funds up-front; throws if insufficient balance
            $this->walletService->debit($merchant->id, (float) $data['amount'], "payout reserve {$transactionId}");

            $payout = Payout::create([
                'transaction_id' => $transactionId,
                'merchant_id' => $merchant->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'INR',
                'status' => 'PENDING',
                'beneficiary_name' => $data['beneficiary_name'] ?? null,
                'beneficiary_account' => $data['beneficiary_account'] ?? null,
                'ifsc_code' => $data['ifsc_code'] ?? null,
                'meta' => $data['meta'] ?? null,
                'wallet_debited' => true,
            ]);

            $this->logger->log($transactionId, 'payout', 'initiated', 'PENDING', [
                'merchant_id' => $merchant->id,
                'amount' => $payout->amount,
                'request' => $data,
            ]);

            return $payout;
        });
    }
}
