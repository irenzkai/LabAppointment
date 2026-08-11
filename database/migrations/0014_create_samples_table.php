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
        // 1. Biological Specimen Registry (PSA/DOH Compliant)
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // e.g. "Blood", "Urine", "Stool", "Swab", "Sputum"
            $table->timestamps();
        });

        // 2. Many-to-Many Pivot mapping services to required specimens
        Schema::create('service_sample', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');

            $table->foreignId('sample_id')
                ->constrained('samples')
                ->onDelete('cascade');

            $table->timestamps();

            // Compound unique constraint protecting database relational integrity
            // Ensures a specimen type can only be mapped once to a specific service
            $table->unique(['service_id', 'sample_id'], 'service_sample_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_sample');
        Schema::dropIfExists('samples');
    }
};