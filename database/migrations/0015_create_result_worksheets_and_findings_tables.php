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
        // 1. Normalized Laboratory Test Parameters Table (1NF Atomic)
        Schema::create('appointment_lab_results', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('appointment_result_id')
                ->constrained('appointment_results')
                ->onDelete('cascade');

            $table->string('parameter_name', 100); // e.g. "WBC Count", "Hemoglobin", "Urine pH"
            $table->string('observed_value', 50);  // e.g. "12.5", "Negative", "Yellow"
            $table->string('reference_range', 100)->nullable(); // e.g. "5-10 x 10^9/L"
            
            $table->timestamps();
        });

        // 2. Normalized Laboratory Details Worksheet (1-to-1 with results folder)
        Schema::create('appointment_lab_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_result_id')
                ->unique() // Restricts to strict 1-to-1 mapping
                ->constrained('appointment_results')
                ->onDelete('cascade');

            $table->string('case_no', 50)->nullable()->unique(); // Unique laboratory case tracking serial
            $table->enum('status', ['pending', 'encoding', 'encoded', 'verified', 'returned'])->default('pending');
            $table->string('scan_path', 255)->nullable();        // Laboratory report PDF/Image scan
            $table->text('return_reason')->nullable();           // Verification rejection notes

            // Clinical Signatories & PRC Licenses (DOH Compliant)
            $table->string('released_by_name', 100)->nullable();      // Medical Technologist 1
            $table->string('released_by_license', 30)->nullable();    // MT PRC License
            $table->string('validated_by_name', 100)->nullable();     // Medical Technologist 2/QC
            $table->string('validated_by_license', 30)->nullable();   // MT PRC License 2
            $table->string('validated_by_name_2', 100)->nullable();   // Pathologist
            $table->string('validated_by_license_2', 30)->nullable(); // Pathologist PRC/DOH License
            
            $table->timestamps();
        });

        // 3. Normalized Medical Certificate Details Worksheet (1-to-1 with results folder)
        Schema::create('appointment_med_certs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_result_id')
                ->unique() // Restricts to strict 1-to-1 mapping
                ->constrained('appointment_results')
                ->onDelete('cascade');

            $table->string('cert_no', 50)->nullable()->unique(); // Unique medical certificate ID
            $table->enum('status', ['pending', 'encoding', 'encoded', 'verified', 'returned'])->default('pending');
            $table->string('scan_path', 255)->nullable();        // MedCert PDF scan override
            $table->text('return_reason')->nullable();
            
            $table->date('date_of_issue')->nullable();
            $table->text('findings')->nullable();
            $table->text('remarks')->nullable();
            $table->string('issued_to', 100)->nullable();         // Patient full name override fallback

            // Clinical Signatory & PRC License (DOH Compliant)
            $table->string('physician_name', 100)->nullable();    // Attending Physician
            $table->string('physician_license', 30)->nullable();  // MD PRC License

            $table->timestamps();
        });

        // 4. Normalized Radiology Details Worksheet (1-to-1 with results folder)
        Schema::create('appointment_radiology_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_result_id')
                ->unique() // Restricts to strict 1-to-1 mapping
                ->constrained('appointment_results')
                ->onDelete('cascade');

            $table->string('case_no', 50)->nullable()->unique(); // Unique radiologic case ID
            $table->enum('status', ['pending', 'encoding', 'encoded', 'verified', 'returned'])->default('pending');
            $table->string('scan_path', 255)->nullable();        // Radiology report PDF scan override
            $table->string('xray_image', 255)->nullable();       // Mandatory raw patient X-Ray image
            $table->text('return_reason')->nullable();

            $table->date('date_of_exam')->nullable();
            $table->string('technique', 150)->nullable();         // e.g. "CHEST PA"
            $table->text('findings')->nullable();
            $table->text('impression')->nullable();

            // Clinical Signatory & PRC License (DOH Compliant)
            $table->string('radiologist_name', 100)->nullable();   // Radiologist
            $table->string('radiologist_license', 30)->nullable(); // Radiologist PRC/DOH License

            $table->timestamps();
        });

        // 5. Normalized Drug Test Details Worksheet (1-to-1 with results folder)
        Schema::create('appointment_drug_tests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_result_id')
                ->unique() // Restricts to strict 1-to-1 mapping
                ->constrained('appointment_results')
                ->onDelete('cascade');

            $table->string('cert_no', 50)->nullable()->unique(); // Unique DOH Drug Test Certificate number
            $table->enum('status', ['pending', 'encoding', 'encoded', 'verified', 'returned'])->default('pending');
            $table->string('scan_path', 255)->nullable();        // Official CCDT Certificate scan
            $table->text('return_reason')->nullable();

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