<?php

namespace App\Console\Commands;

use App\Services\PaymentProcessingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingPayments extends Command
{
    /**
     * php artisan payments:process
     * php artisan payments:process --batch=100
     */
    protected $signature = 'payments:process {--batch=50 : Max rows to process per table per run}';

    protected $description = 'Scan PENDING pay-ins and payouts and resolve them to SUCCESS/FAILED/PENDING';

    public function handle(PaymentProcessingService $service): int
    {
        $batchSize = (int) $this->option('batch');

        $this->info("Processing pending pay-ins (batch={$batchSize})...");
        $payinSummary = $service->processPendingPayins($batchSize);
        $this->table(['checked', 'success', 'failed', 'still_pending'], [$payinSummary]);

        $this->info("Processing pending payouts (batch={$batchSize})...");
        $payoutSummary = $service->processPendingPayouts($batchSize);
        $this->table(['checked', 'success', 'failed', 'still_pending'], [$payoutSummary]);

        Log::channel('single')->info('payments:process run complete', [
            'payins' => $payinSummary,
            'payouts' => $payoutSummary,
        ]);

        $this->info('Done.');

        return self::SUCCESS;
    }
}
