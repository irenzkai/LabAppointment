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
            $table->string('name'); // service name [160]
            $table->decimal('price', 8, 2); // service price [160]
            $table->text('description'); // service description [160]
            $table->text('preparation'); // preparation requirement [160]
            
            $table->unsignedInteger('estimated_time')->nullable();
            $table->string('category')->default('individual'); // individual, package [160]
            $table->string('gender_restriction')->default('both'); // male, female, both [160]
            $table->boolean('is_available')->default(true); // availability toggle [160]
            $table->timestamps();

            // AUDIT & DATA RETENTION: SoftDeletes (Re-activation archive, no expiry) [102]
            $table->softDeletes(); // Adds 'deleted_at' column for compliant deactivations
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