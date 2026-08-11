<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();

            // 1. Gateway Identifiers & Assets
            $table->string('name', 50)->unique();    // e.g. "GCASH", "MAYA", "GRABPAY"
            $table->string('logo', 255)->nullable(); // Path to public brand logotype
            $table->string('qr_code', 255);          // Path to merchant's dynamic scan-to-pay QR image

            // 2. Operational parameters
            $table->boolean('is_active')->default(true); // Status toggle
            
            $table->timestamps();

            // AUDIT & DATA RETENTION COMPLIANCE [102]
            // Permits archiving of gateways while preserving payment logs
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_providers');
    }
};