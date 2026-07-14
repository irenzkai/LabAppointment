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
        // 1. Normalized Laboratory Test Parameters Table (replaces lab_data JSON results)
        Schema::create('appointment_lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')->constrained('appointment_results')->onDelete('cascade');
            $table->string('parameter_name'); // e.g., 'WBC Count', 'Hemoglobin', 'Specific Gravity'
            $table->string('observed_value');
            $table->string('reference_range')->nullable();
            $table->timestamps();
        });

        // 2. Normalized Laboratory Metadata & Signatories Table (replaces lab_data JSON meta)
        Schema::create('appointment_lab_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')->constrained('appointment_results')->onDelete('cascade');
            $table->string('case_no')->nullable();
            $table->string('released_by_name')->nullable();
            $table->string('released_by_license')->nullable();
            $table->string('validated_by_name')->nullable();
            $table->string('validated_by_license')->nullable();
            $table->string('validated_by_name_2')->nullable();
            $table->string('validated_by_license_2')->nullable();
            $table->timestamps();
        });

        // 3. Normalized Medical Certificate Findings Table (replaces med_cert_data JSON)
        Schema::create('appointment_med_certs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')->constrained('appointment_results')->onDelete('cascade');
            $table->string('cert_no')->nullable();
            $table->date('date_of_issue')->nullable();
            $table->text('findings')->nullable();
            $table->text('remarks')->nullable();
            $table->string('issued_to')->nullable();
            $table->string('physician_name')->nullable();
            $table->string('physician_license')->nullable();
            $table->timestamps();
        });

        // 4. Normalized Radiology Reports Findings Table (replaces radio_data JSON)
        Schema::create('appointment_radiology_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')->constrained('appointment_results')->onDelete('cascade');
            $table->string('case_no')->nullable();
            $table->date('date_of_exam')->nullable();
            $table->string('technique')->nullable(); // e.g., 'CHEST PA'
            $table->text('findings')->nullable();
            $table->text('impression')->nullable();
            $table->string('radiologist_name')->nullable();
            $table->string('radiologist_license')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_radiology_reports');
        Schema::dropIfExists('appointment_med_certs');
        Schema::dropIfExists('appointment_lab_details');
        Schema::dropIfExists('appointment_lab_results');
    }
};