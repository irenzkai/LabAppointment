<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            // 1. RELATIONSHIPS
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('dependent_id')->nullable()->constrained()->onDelete('set null');

            // 2. BULK PATIENT IDENTITY
            $table->string('patient_name')->nullable();
            $table->string('patient_email')->nullable();
            $table->string('patient_phone')->nullable();
            $table->string('patient_sex')->nullable();
            $table->date('patient_birthdate')->nullable();

            // 3. ORGANIZATION & BATCHING
            $table->string('organization_name')->nullable();
            $table->string('batch_id')->nullable(); 
            
            // 4. SCHEDULE & LOCATION
            $table->date('appointment_date');
            $table->time('time_slot');
            $table->text('patient_address')->nullable();
            
            // 5. STATUS LOGIC
            $table->string('status')->default('pending'); 
            $table->text('return_reason')->nullable(); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};