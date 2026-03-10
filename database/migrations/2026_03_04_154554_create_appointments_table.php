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
            // The Patient
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // The Schedule
            $table->date('appointment_date');
            $table->time('time_slot');
            
            // Status Logic: pending, approved, returned, completed
            $table->string('status')->default('pending'); 
            $table->text('return_reason')->nullable(); 

            // Prevent two different people from booking the exact same slot
            $table->unique(['appointment_date', 'time_slot']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};