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
        Schema::create('laboratory_histories', function (Blueprint $table) {
            $table->id();

            // 1. Relational 1-to-1 Foreign Key linking to the patient
            // unique() guarantees each patient has exactly one consolidated history folder header
            $table->foreignId('user_id')
                ->unique() 
                ->constrained('users')
                ->onDelete('cascade');

            // 2. Handshake Permission States (RA 10173 Compliance)
            // Explicitly constrained ENUM prevents invalid or bypassed authorization levels
            $table->enum('permission_status', [
                'none', 
                'pending_patient', 
                'pending_staff', 
                'granted'
            ])->default('none');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_histories');
    }
};