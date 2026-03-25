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
            $table->json('included_reports')->nullable();
            // Manual Data (JSON stores multiple fields easily)
            $table->json('lab_data')->nullable();
            $table->json('med_cert_data')->nullable();
            $table->json('drug_test_data')->nullable();
            $table->json('radio_data')->nullable();
            // Scan/File Paths
            $table->string('lab_scan')->nullable();
            $table->string('med_cert_scan')->nullable();
            $table->string('drug_test_scan')->nullable();
            $table->string('radio_scan')->nullable();
            $table->string('xray_image')->nullable();
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
