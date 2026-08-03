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
        // 1. Normalized Laboratory Test Parameters Table
        Schema::create('appointment_lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')
                  ->constrained('appointment_results')
                  ->onDelete('cascade');
            $table->string('parameter_name'); // e.g., 'WBC Count', 'Hemoglobin'
            $table->string('observed_value');
            $table->string('reference_range')->nullable();
            $table->timestamps();
        });

        // 2. Normalized Laboratory Details (Worksheet)
        Schema::create('appointment_lab_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')
                  ->constrained('appointment_results')
                  ->onDelete('cascade');
            $table->string('case_no')->nullable()->unique();
            $table->string('status')->default('pending'); // pending, encoding, encoded, verified, returned
            $table->string('scan_path')->nullable(); // lab_scan
            $table->text('return_reason')->nullable(); // lab_return_reason
            $table->string('released_by_name')->nullable();
            $table->string('released_by_license')->nullable();
            $table->string('validated_by_name')->nullable();
            $table->string('validated_by_license')->nullable();
            $table->string('validated_by_name_2')->nullable();
            $table->string('validated_by_license_2')->nullable();
            $table->timestamps();
        });

        // 3. Normalized Medical Certificate Details (Worksheet)
        Schema::create('appointment_med_certs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')
                  ->constrained('appointment_results')
                  ->onDelete('cascade');
            $table->string('cert_no')->nullable()->unique();
            $table->string('status')->default('pending'); // pending, encoding, encoded, verified, returned
            $table->string('scan_path')->nullable(); // med_cert_scan
            $table->text('return_reason')->nullable(); // med_return_reason
            $table->date('date_of_issue')->nullable();
            $table->text('findings')->nullable();
            $table->text('remarks')->nullable();
            $table->string('issued_to')->nullable();
            $table->string('physician_name')->nullable();
            $table->string('physician_license')->nullable();
            $table->timestamps();
        });

        // 4. Normalized Radiology Details (Worksheet)
        Schema::create('appointment_radiology_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')
                  ->constrained('appointment_results')
                  ->onDelete('cascade');
            $table->string('case_no')->nullable()->unique();
            $table->string('status')->default('pending'); // pending, encoding, encoded, verified, returned
            $table->string('scan_path')->nullable(); // radio_scan
            $table->string('xray_image')->nullable(); // FIXED: Added xray_image column natively in the worksheet table
            $table->text('return_reason')->nullable(); // radio_return_reason
            $table->date('date_of_exam')->nullable();
            $table->string('technique')->nullable();
            $table->text('findings')->nullable();
            $table->text('impression')->nullable();
            $table->string('radiologist_name')->nullable();
            $table->string('radiologist_license')->nullable();
            $table->timestamps();
        });

        // 5. Normalized Drug Test Details (Worksheet)
        Schema::create('appointment_drug_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')
                  ->constrained('appointment_results')
                  ->onDelete('cascade');
            $table->string('cert_no')->nullable()->unique();
            $table->string('status')->default('pending'); // pending, encoding, encoded, verified, returned
            $table->string('scan_path')->nullable(); // drug_test_scan
            $table->text('return_reason')->nullable(); // drug_return_reason
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_drug_tests');
        Schema::dropIfExists('appointment_radiology_reports');
        Schema::dropIfExists('appointment_med_certs');
        Schema::dropIfExists('appointment_lab_details');
        Schema::dropIfExists('appointment_lab_results');
    }
};