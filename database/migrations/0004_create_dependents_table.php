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
        Schema::create('dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Split Name Fields (1NF Atomic)
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            
            $table->date('birthdate');
            $table->string('sex');
            $table->string('phone')->nullable();
            $table->string('relationship'); // e.g. Son, Daughter, Parent, Spouse
            
            // Split Address Fields (3NF Atomic - PSGC API Compatible)
            $table->string('street');
            $table->string('barangay');
            $table->string('city');
            $table->string('province');

            $table->timestamps();
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