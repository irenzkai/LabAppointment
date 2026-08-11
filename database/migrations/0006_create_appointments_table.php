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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // 1. RELATIONSHIPS
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
                
            $table->foreignId('dependent_id')
                ->nullable()
                ->constrained('dependents')
                ->onDelete('set null'); // Keeps historical logs if parent removes child card

            // 2. BULK PATIENT IDENTITY SNAPSHOTS (PSA / Ph-Standard Mapping)
            $table->string('patient_first_name', 60)->nullable();
            $table->string('patient_middle_name', 60)->nullable();
            $table->string('patient_last_name', 60)->nullable();
            $table->string('patient_suffix', 10)->nullable(); // Added to match new suffix schema
            $table->string('patient_name', 255)->nullable();  // Full display name snapshot
            $table->string('patient_email', 191)->nullable();
            $table->string('patient_phone', 11)->nullable();  // Snapshotted PH standard mobile number
            $table->enum('patient_sex', ['Male', 'Female'])->nullable();
            $table->date('patient_birthdate')->nullable();

            // 3. MEDICAL ATTACHMENTS (Optional)
            $table->string('referral_note', 255)->nullable(); // File path for PDF/Image referrals

            // 4. ORGANIZATION & BATCHING
            $table->string('organization_name', 100)->nullable(); // Company name for bulk bookings
            $table->string('batch_id', 10)->nullable();            // Batch identifier for corporate groups

            // 5. SCHEDULE & LOCATION
            $table->date('appointment_date');
            $table->time('time_slot');

            // Decomposed composite patient_address snapshot (PSGC API Compatible)
            $table->string('patient_street', 150)->nullable();
            $table->string('patient_barangay', 100)->nullable();
            $table->string('patient_city', 100)->nullable();       // City or Municipality
            $table->string('patient_province', 100)->nullable();

            // 6. PAYMENT STATE & SETTLEMENT METHODS
            $table->enum('payment_method', ['Cash', 'Cashless']);
            $table->enum('payment_status', ['unpaid', 'paid', 'invalid', 'refunded'])->default('unpaid');
            $table->string('payment_receipt', 255)->nullable(); // Path to proof-of-payment file
            $table->decimal('payment_amount', 8, 2)->default(0.00); // Standard PH decimal scale

            // 7. STATUS LOGIC & SOFT DELETION
            $table->enum('status', [
                'pending', 
                'approved', 
                'tested', 
                'encoded', 
                'released', 
                'canceled', 
                'returned', 
                'retest'
            ])->default('pending');
            $table->boolean('deleted_by_patient')->default(false); // Soft-delete toggle for patients
            $table->text('return_reason')->nullable();             // Auditable rejection notes

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