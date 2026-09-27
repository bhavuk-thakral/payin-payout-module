<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ProcessPendingPayments::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Runs every minute; withoutOverlapping() ensures a slow run never
        // overlaps with the next tick, and each row is additionally guarded
        // by a DB row lock inside PaymentProcessingService.
        $schedule->command('payments:process --batch=' . env('PAYMENT_CRON_BATCH_SIZE', 50))
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/payments-cron.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
