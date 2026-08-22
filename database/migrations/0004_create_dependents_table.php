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
        Schema::create('dependents', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key linking directly to the parent user
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // 1. Split Name Fields (1NF Atomic) with strict boundaries
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->nullable(); // Nullable per PSA standards
            $table->string('last_name', 60);
            $table->string('suffix', 10)->nullable(); // Added for minor suffixes (e.g. JR., III)

            // 2. Minor Profile Details
            $table->date('birthdate');
            $table->enum('sex', ['Male', 'Female']);
            $table->string('phone', 11)->nullable(); // Nullable but constrained if provided

            // 3. Split Address Fields (3NF Atomic - PSGC API Compatible)
            $table->string('street', 150);
            $table->string('barangay', 100);
            $table->string('city', 100); // City or Municipality
            $table->string('province', 100);
            $table->timestamps();

            // AUDIT & DATA RETENTION COMPLIANCE [102]
            // Retains minor records safely for medical deactivations
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependents');
    }
};