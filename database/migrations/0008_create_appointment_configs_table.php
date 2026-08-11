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
        Schema::create('appointment_configs', function (Blueprint $table) {
            $table->id();

            // 1. Calendar Constraints (0 = Sunday to 6 = Saturday)
            // Unique indexes prevent duplicate or conflicting schedules for the same day/date
            $table->unsignedTinyInteger('day_of_week')->nullable()->unique(); 
            $table->date('specific_date')->nullable()->unique(); 

            // 2. Operational Status & Hours
            $table->boolean('is_open')->default(true);
            $table->time('opening_time')->default('08:00:00'); // PH clinical standard default
            $table->time('closing_time')->default('17:00:00'); // PH clinical standard default
            $table->unsignedSmallInteger('slot_duration')->default(60); // In minutes, strictly positive

            // 3. Mid-day Break Parameters
            $table->boolean('has_lunch_break')->default(false);
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();

            // 4. Load Capacities & Lead Time Limits
            $table->unsignedSmallInteger('max_patients_per_slot')->default(2); // Maximum patient quota per slot
            $table->unsignedSmallInteger('lead_time_hours')->default(2);       // Booking cutoff buffer in hours

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_configs');
    }
};