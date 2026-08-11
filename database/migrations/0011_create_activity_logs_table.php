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

            // 1. Relational Handshakes (DPA RA 10173 Compliance)
            // Ensures audit logs persist even if parents, patients, or appointments are deleted
            $table->foreignId('user_id')
                ->nullable() 
                ->constrained('users')
                ->onDelete('set null');

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->onDelete('set null');

            // 2. Snapshotted Identity and Audit Details
            // Indexed to support high-speed administrative live searches
            $table->string('patient_name', 255)->index(); 
            $table->string('action', 50)->index(); // e.g. "SENSITIVE DATA ACCESS", "BOOKED", "ENCODED"
            $table->text('reason')->nullable();    // Justification from Reason-Gate modals

            $table->timestamps(); // Serves as the indexed audit timestamp (created_at desc)
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