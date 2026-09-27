<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // e.g. POUT-20260926-XXXXXXXX
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED'])->default('PENDING');
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_account')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->boolean('wallet_debited')->default(false); // debited up-front at initiation (reserved)
            $table->boolean('wallet_reversed')->default(false); // reversed if payout finally fails
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['status']);
            $table->index(['merchant_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
