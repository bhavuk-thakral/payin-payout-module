<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->index();
            $table->string('type');      // payin | payout
            $table->string('event');     // initiated | status_changed | processed | error
            $table->string('status')->nullable(); // status at the time of this log line
            $table->json('details')->nullable();  // request payload / processing details / error message
            $table->timestamps();

            $table->index(['type', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
