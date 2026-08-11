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
        Schema::create('workstation_audits', function (Blueprint $table) {
            $table->id();

            // 1. Relational Pivot ID
            $table->foreignId('appointment_result_id')
                ->constrained('appointment_results')
                ->onDelete('cascade');

            $table->string('workstation_type', 50); // e.g. "lab", "med", "radio", "drug", "custom_1"

            // 2. V1 Encoder Audit trail (PSA/DOH Compliant)
            // Keeps sign-off trails intact even if encoder employee account is deleted
            $table->foreignId('v1_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('v1_by_name', 255)->nullable(); // Snapshot of encoder name
            $table->timestamp('v1_at')->nullable();

            // 3. V2 Verifier Audit trail (PSA/DOH Compliant)
            // Keeps sign-off trails intact even if verifier employee account is deleted
            $table->foreignId('v2_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('v2_by_name', 255)->nullable(); // Snapshot of verifier name (e.g., licensed physician)
            $table->timestamp('v2_at')->nullable();

            $table->timestamps();

            // 4. Compound unique constraint protecting audit trail sequence
            // Prevents multiple overlapping logs for the same workstation within one folder
            $table->unique(['appointment_result_id', 'workstation_type'], 'workstation_audits_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workstation_audits');
    }
};