<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payins', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // e.g. PIN-20260926-XXXXXXXX
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED'])->default('PENDING');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('payment_method')->nullable(); // card / upi / netbanking etc (free text for this assignment)
            $table->json('meta')->nullable();              // raw extra payload from the request
            $table->timestamp('processed_at')->nullable();  // set once the cron picks a final status
            $table->boolean('wallet_credited')->default(false); // guards against double-crediting
            $table->unsignedTinyInteger('attempts')->default(0); // how many times cron looked at this record
            $table->timestamps();

            $table->index(['status']);
            $table->index(['merchant_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payins');
    }
};
