<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_code')->unique(); // public-facing unique code, e.g. MERCH-XXXXX
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('api_key')->unique(); // used to authenticate API requests
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
