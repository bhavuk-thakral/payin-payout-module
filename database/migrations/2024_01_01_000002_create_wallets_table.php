<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')
                ->unique() // one wallet per merchant
                ->constrained('merchants')
                ->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);       // available balance
            $table->decimal('locked_balance', 15, 2)->default(0); // reserved/processing funds (future use)
            $table->string('currency', 3)->default('INR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
