<?php
// database/migrations/0006_create_appointments_table.php

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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // 1. RELATIONSHIPS
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('dependent_id')->nullable()->constrained()->onDelete('set null');

            // 2. BULK PATIENT IDENTITY SNAPSHOTS (1NF Atomic Names & Details)
            $table->string('patient_first_name')->nullable();
            $table->string('patient_middle_name')->nullable();
            $table->string('patient_last_name')->nullable();
            $table->string('patient_name')->nullable(); // Preserved for backwards-compatibility with display queries
            $table->string('patient_email')->nullable();
            $table->string('patient_phone')->nullable();
            $table->string('patient_sex')->nullable();
            $table->date('patient_birthdate')->nullable();

            // 3. MEDICAL ATTACHMENTS (Optional)
            $table->string('referral_note')->nullable(); // Stores file path for PDF or image referrals

            // 4. ORGANIZATION & BATCHING
            $table->string('organization_name')->nullable();
            $table->string('batch_id')->nullable(); 

            // 5. SCHEDULE & LOCATION
            $table->date('appointment_date');
            $table->time('time_slot'); 

            // Decomposed composite patient_address snapshot into 3NF atomic columns
            $table->string('patient_street')->nullable();
            $table->string('patient_barangay')->nullable();
            $table->string('patient_city')->nullable();
            $table->string('patient_province')->nullable();

            // 6. PAYMENT STATE & SETTLEMENT METHODS
            $table->string('payment_method'); // Cash, Cashless
            $table->string('payment_status')->default('unpaid'); // unpaid, paid
            $table->string('payment_receipt')->nullable(); // Stores path for uploaded proof of payment receipts

            // 7. STATUS LOGIC & SOFT DELETION
            $table->string('status')->default('pending'); // pending, approved, tested, encoded, released
            $table->boolean('deleted_by_patient')->default(false); // patient soft-delete flag for expired slots
            $table->text('return_reason')->nullable(); 

            // 8. WORKFLOW TIMESTAMPS
            $table->timestamp('tested_at')->nullable();
            $table->dateTime('result_estimated_at')->nullable();
            $table->timestamp('results_released_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};