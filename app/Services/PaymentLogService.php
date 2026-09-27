<?php

namespace App\Services;

use App\Models\PaymentLog;
use Illuminate\Support\Facades\Log;

/**
 * Centralised logger for every payment-related event.
 * Writes to both the application log file (for ops/debugging)
 * and the payment_logs table (for auditability / admin UI).
 */
class PaymentLogService
{
    public function log(string $transactionId, string $type, string $event, ?string $status = null, array $details = []): void
    {
        PaymentLog::create([
            'transaction_id' => $transactionId,
            'type' => $type,     // payin | payout
            'event' => $event,   // initiated | status_changed | processed | error
            'status' => $status,
            'details' => $details,
        ]);

        Log::channel('single')->info("[{$type}] {$event}", array_merge([
            'transaction_id' => $transactionId,
            'status' => $status,
        ], $details));
    }
}
