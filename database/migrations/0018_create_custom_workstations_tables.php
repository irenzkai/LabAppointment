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

            // 1. Relational Many-to-1 Foreign Key
            // Linked to results folder; Cascade deletes ensure orphan records are auto-removed
            $table->foreignId('appointment_result_id')
                ->constrained('appointment_results')
                ->onDelete('cascade');

            // 2. Custom Worksheet Parameters
            $table->string('name', 100); // e.g. "ECG", "Dental Clearance", "Pap Smear"
            $table->enum('status', ['pending', 'encoding', 'encoded', 'verified', 'returned'])->default('pending');
            $table->string('cert_no', 50)->nullable()->index();  // Indexed for quick validation search queries
            $table->string('scan_path', 255)->nullable();        // Path to clinical document file scan

            // 3. Correction & Auditor logs
            $table->text('return_reason')->nullable(); // Verifier correction details

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