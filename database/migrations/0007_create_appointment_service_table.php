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
        Schema::create('appointment_service', function (Blueprint $table) {
            $table->id();

            // 1. Explicit relational foreign keys
            $table->foreignId('appointment_id')
                ->constrained('appointments')
                ->onDelete('cascade');

            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');

            $table->timestamps();

            // 2. Compound unique constraint protecting database relational integrity
            // Prevents duplicate associations (e.g., adding "CBC" twice to the same booking)
            $table->unique(['appointment_id', 'service_id'], 'appointment_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
    }
};