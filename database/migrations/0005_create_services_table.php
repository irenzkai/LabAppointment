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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            // 1. Core Clinical Service Identifiers
            $table->string('name', 100)->unique(); // Prevents duplicate test creations
            $table->decimal('price', 8, 2);        // Standard PHP (Philippine Peso) representation
            $table->text('description');           // Detailed examination scope
            $table->text('preparation');           // Prep guidelines (e.g. "Fasting required")

            // 2. Operational parameters
            $table->unsignedInteger('estimated_time')->nullable(); // Duration in minutes
            $table->enum('category', ['individual', 'package'])->default('individual');
            $table->enum('gender_restriction', ['male', 'female', 'both'])->default('both');
            $table->boolean('is_available')->default(true); // Availability toggle

            $table->timestamps();

            // AUDIT & DATA RETENTION COMPLIANCE [102]
            // Preserves service catalog integrity for historical appointments
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};