<?php

namespace App\Services;

use App\Models\Payin;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Core cron logic: scans PENDING pay-ins/payouts and randomly resolves
 * them to SUCCESS / FAILED / PENDING, updating the wallet where required.
 *
 * Every record is processed inside its own DB transaction with a row
 * lock (SELECT ... FOR UPDATE) so that:
 *   - two overlapping cron runs can never process the same row twice
 *   - a row already resolved (no longer PENDING) is simply skipped
 */
class PaymentProcessingService
{
    protected array $possibleOutcomes = ['SUCCESS', 'FAILED', 'PENDING'];

    public function __construct(
        protected WalletService $walletService,
        protected PaymentLogService $logger,
    ) {
    }

    public function processPendingPayins(int $batchSize = 50): array
    {
        $summary = ['checked' => 0, 'success' => 0, 'failed' => 0, 'still_pending' => 0];

        $ids = Payin::pending()->orderBy('id')->limit($batchSize)->pluck('id');

        foreach ($ids as $id) {
            $summary['checked']++;
            $outcome = $this->processSinglePayin($id);
            if ($outcome) {
                $summary[$this->summaryKey($outcome)]++;
            }
        }

        return $summary;
    }

    public function processPendingPayouts(int $batchSize = 50): array
    {
        $summary = ['checked' => 0, 'success' => 0, 'failed' => 0, 'still_pending' => 0];

        $ids = Payout::pending()->orderBy('id')->limit($batchSize)->pluck('id');

        foreach ($ids as $id) {
            $summary['checked']++;
            $outcome = $this->processSinglePayout($id);
            if ($outcome) {
                $summary[$this->summaryKey($outcome)]++;
            }
        }

        return $summary;
    }

    protected function summaryKey(string $outcome): string
    {
        return match ($outcome) {
            'SUCCESS' => 'success',
            'FAILED' => 'failed',
            default => 'still_pending',
        };
    }

    protected function randomOutcome(): string
    {
        return $this->possibleOutcomes[array_rand($this->possibleOutcomes)];
    }

    protected function processSinglePayin(int $payinId): ?string
    {
        try {
            return DB::transaction(function () use ($payinId) {
                /** @var Payin|null $payin */
                $payin = Payin::where('id', $payinId)->lockForUpdate()->first();

                // Already processed by another run, or deleted meanwhile — skip.
                if (! $payin || $payin->status !== 'PENDING') {
                    return null;
                }

                $outcome = $this->randomOutcome();
                $payin->attempts++;

                if ($outcome === 'PENDING') {
                    $payin->save(); // just record the attempt, stays pending for next cron run
                    $this->logger->log($payin->transaction_id, 'payin', 'processed', 'PENDING', [
                        'attempts' => $payin->attempts,
                    ]);

                    return 'PENDING';
                }

                $payin->status = $outcome;
                $payin->processed_at = now();

                if ($outcome === 'SUCCESS' && ! $payin->wallet_credited) {
                    $this->walletService->credit(
                        $payin->merchant_id,
                        (float) $payin->amount,
                        "payin {$payin->transaction_id}"
                    );
                    $payin->wallet_credited = true;
                }

                $payin->save();

                $this->logger->log($payin->transaction_id, 'payin', 'processed', $outcome, [
                    'attempts' => $payin->attempts,
                    'wallet_credited' => $payin->wallet_credited,
                ]);

                return $outcome;
            });
        } catch (\Throwable $e) {
            Log::channel('single')->error('Payin processing error', [
                'payin_id' => $payinId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function processSinglePayout(int $payoutId): ?string
    {
        try {
            return DB::transaction(function () use ($payoutId) {
                /** @var Payout|null $payout */
                $payout = Payout::where('id', $payoutId)->lockForUpdate()->first();

                if (! $payout || $payout->status !== 'PENDING') {
                    return null;
                }

                $outcome = $this->randomOutcome();
                $payout->attempts++;

                if ($outcome === 'PENDING') {
                    $payout->save();
                    $this->logger->log($payout->transaction_id, 'payout', 'processed', 'PENDING', [
                        'attempts' => $payout->attempts,
                    ]);

                    return 'PENDING';
                }

                $payout->status = $outcome;
                $payout->processed_at = now();

                // funds were already reserved (debited) at initiation.
                // SUCCESS: nothing further to do to the wallet.
                // FAILED: give the reserved funds back, exactly once.
                if ($outcome === 'FAILED' && $payout->wallet_debited && ! $payout->wallet_reversed) {
                    $this->walletService->reverse(
                        $payout->merchant_id,
                        (float) $payout->amount,
                        "payout {$payout->transaction_id}"
                    );
                    $payout->wallet_reversed = true;
                }

                $payout->save();

                $this->logger->log($payout->transaction_id, 'payout', 'processed', $outcome, [
                    'attempts' => $payout->attempts,
                    'wallet_reversed' => $payout->wallet_reversed,
                ]);

                return $outcome;
            });
        } catch (\Throwable $e) {
            Log::channel('single')->error('Payout processing error', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
