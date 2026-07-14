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
            $table->foreignId('laboratory_history_id')->constrained('laboratory_histories')->onDelete('cascade');
            $table->date('date_of_record');
            $table->string('requested_by');
            
            // Patient Demographics Snapshot
            $table->string('patient_first_name');
            $table->string('patient_middle_name')->nullable();
            $table->string('patient_last_name');
            $table->string('patient_name'); // Composite Full Name
            $table->integer('age');
            $table->string('sex');
            
            // Address Snapshot
            $table->string('patient_street');
            $table->string('patient_barangay');
            $table->string('patient_city');
            $table->string('patient_province');
            $table->text('patient_address'); // Composite Full Address

            $table->timestamps();
        });

        // 2. Stores multiple clinical scan files attached to a single record
        Schema::create('laboratory_history_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_record_id')->constrained('laboratory_history_records')->onDelete('cascade');
            $table->string('label'); // e.g., 'Hematology Report'
            $table->string('file_path');
            $table->string('certificate_no')->nullable(); // FIXED: Added optional certificate number for historical scans
            $table->timestamps();
        });

        // 3. Stores multiple procedure badges tagged to a single record
        Schema::create('laboratory_history_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_record_id')->constrained('laboratory_history_records')->onDelete('cascade');
            $table->string('procedure_name'); // e.g., 'WBC Count'
            $table->timestamps();
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