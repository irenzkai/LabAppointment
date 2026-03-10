<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('appointment_configs', function (Blueprint $table) {
            $table->id();
            $table->time('opening_time')->default('08:00');
            $table->time('closing_time')->default('17:00');
            $table->integer('slot_duration')->default(60); // in minutes
            $table->boolean('has_lunch_break')->default(false);
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();
            $table->integer('max_patients_per_slot')->default(1);
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
