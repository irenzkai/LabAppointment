<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::create('appointment_configs', function (Blueprint $table) {
            $table->id();
            // Use day_of_week (0-6) for recurring rules
            $table->integer('day_of_week')->nullable(); 
            // Use specific_date for holidays or one-off changes
            $table->date('specific_date')->nullable(); 
            
            $table->boolean('is_open')->default(true);
            $table->time('opening_time')->default('08:00');
            $table->time('closing_time')->default('17:00');
            $table->integer('slot_duration')->default(60); 
            
            $table->boolean('has_lunch_break')->default(false);
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();
            
            $table->integer('max_patients_per_slot')->default(2);
            // New: Prevent booking X hours before the slot
            $table->integer('lead_time_hours')->default(2); 
            
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('appointment_configs');
    }
};