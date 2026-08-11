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
        Schema::create('appointment_results', function (Blueprint $table) {
            $table->id();

            // 1. Relational 1-to-1 Foreign Key
            // unique() is critical to enforce that an appointment has EXACTLY one results folder
            $table->foreignId('appointment_id')
                ->unique() 
                ->constrained('appointments')
                ->onDelete('cascade');

            // 2. Dynamic Worksheets Tracker
            $table->json('included_reports')->nullable(); // Tracks active worksheet states (e.g., ['lab', 'radio'])

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_results');
    }
};