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
        // 1. Stores metadata and patient demographics snapshot for each digitized report
        Schema::create('laboratory_history_records', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('laboratory_history_id')
                ->constrained('laboratory_histories')
                ->onDelete('cascade');

            $table->date('date_of_record');
            $table->string('requested_by', 100); // e.g. "INDIVIDUAL", "MEDSCREEN CLINIC", "PSA"

            // Patient Demographics Snapshot (Symmetric with core user schema)
            $table->string('patient_first_name', 60);
            $table->string('patient_middle_name', 60)->nullable(); // Nullable per PSA guidelines
            $table->string('patient_last_name', 60);
            $table->string('patient_name', 255); // Composite full display name
            $table->unsignedTinyInteger('age');  // Constrained to unsigned byte
            $table->enum('sex', ['Male', 'Female']);

            // Address Snapshot (PSGC API Compatible)
            $table->string('patient_street', 150);
            $table->string('patient_barangay', 100);
            $table->string('patient_city', 100);       // City or Municipality
            $table->string('patient_province', 100);
            $table->string('patient_address', 255);    // Composite full address

            $table->timestamps();
        });

        // 2. Stores multiple clinical scan files attached to a single record
        Schema::create('laboratory_history_scans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('history_record_id')
                ->constrained('laboratory_history_records')
                ->onDelete('cascade');

            $table->string('label', 100);        // e.g. "Hematology Report", "Urinalysis Scans"
            $table->string('file_path', 255);
            $table->string('certificate_no', 50)->nullable()->index(); // Indexed for public verification searches

            $table->timestamps();
        });

        // 3. Stores multiple procedure badges tagged to a single record
        Schema::create('laboratory_history_procedures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('history_record_id')
                ->constrained('laboratory_history_records')
                ->onDelete('cascade');

            $table->string('procedure_name', 100); // e.g. "WBC Count", "Fecalysis"

            $table->timestamps();

            // Compound unique constraint prevents duplicate procedures on the same archival record
            $table->unique(['history_record_id', 'procedure_name'], 'history_procedure_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_history_procedures');
        Schema::dropIfExists('laboratory_history_scans');
        Schema::dropIfExists('laboratory_history_records');
    }
};