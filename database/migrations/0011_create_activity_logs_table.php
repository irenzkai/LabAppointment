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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            /**
             * FIXED: Added nullable() and onDelete('set null')
             * This allows the System Admin to purge a user account while keeping 
             * the audit trail intact for clinical compliance.
             */
            $table->foreignId('user_id')
                ->nullable() 
                ->constrained('users')
                ->onDelete('set null');

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            /**
             * Snapshots are preserved even if the user/appointment is deleted
             */
            $table->string('patient_name');
            $table->string('action'); // e.g., "BOOKED", "ENCODED", "PERMANENT ACCOUNT DELETION"
            $table->text('reason')->nullable(); // Justification provided via Reason-Gate modals
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};