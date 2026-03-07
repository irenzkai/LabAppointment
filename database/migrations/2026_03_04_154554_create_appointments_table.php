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
            // Connect to User (The patient)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Connect to Service
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            
            $table->date('appointment_date');
            
            // Status: pending, approved, returned
            $table->string('status')->default('pending'); 
            $table->text('return_reason')->nullable(); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('return_reason');
        });
    }
};