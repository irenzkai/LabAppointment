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
        Schema::create('appointment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            
            // 1. WORKSTATION STATUSES
            // Statuses: pending, encoding, encoded, verified, returned
            $table->string('lab_status')->default('pending');
            $table->string('med_status')->default('pending');
            $table->string('radio_status')->default('pending');
            $table->string('drug_status')->default('pending');
            $table->json('included_reports')->nullable();

            // 2. DATA PAYLOADS (JSON)
            // Stores manual result values and clinical signatories (Pathologist/Radiologist)
            $table->json('lab_data')->nullable();
            $table->json('med_cert_data')->nullable();
            $table->json('drug_test_data')->nullable();
            $table->json('radio_data')->nullable();

            // 3. FILE PATHS (SCANS & IMAGES)
            $table->string('lab_scan')->nullable();
            $table->string('med_cert_scan')->nullable();
            $table->string('drug_test_scan')->nullable();
            $table->string('radio_scan')->nullable();
            $table->string('xray_image')->nullable();

            // 4. INTERNAL CORRECTION LOGIC (Return Reasons)
            $table->text('lab_return_reason')->nullable();
            $table->text('med_return_reason')->nullable();
            $table->text('radio_return_reason')->nullable();
            $table->text('drug_return_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_results');
    }
};