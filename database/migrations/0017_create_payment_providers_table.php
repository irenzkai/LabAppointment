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
            $table->string('name');
            $table->string('logo')->nullable(); // Optional path to logotypes [176]
            $table->string('qr_code'); // Path to uploaded scan QR [176]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // AUDIT & DATA RETENTION: SoftDeletes (Re-activation archive, no expiry) [102]
            $table->softDeletes(); // Adds 'deleted_at' column for compliant deactivations
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