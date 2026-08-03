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
        // Creates the normalized, appointment-scoped Custom Workstation Results table (Worksheets)
        Schema::create('custom_workstation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')
                  ->constrained('appointment_results')
                  ->onDelete('cascade');
            $table->string('name'); // Name of the dynamic custom worksheet (e.g., 'ECG', 'Dental Clearance')
            $table->string('status')->default('pending'); // pending, encoding, encoded, verified, returned
            $table->string('cert_no')->nullable(); // Certificate / Reference ID tracking number
            $table->string('scan_path')->nullable(); // Uploaded clinical document scan path
            $table->text('return_reason')->nullable(); // Verifier correction notes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_workstation_results');
    }
};