<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // service name
            $table->decimal('price', 8, 2); // service price
            $table->text('description'); // service description
            $table->text('preparation'); // preparation requirement
            $table->string('sample_required')->nullable();
            $table->unsignedInteger('estimated_time')->nullable();
            $table->string('category')->default('individual'); // individual, package
            $table->string('gender_restriction')->default('both'); // male, female, both
            $table->boolean('is_available')->default(true); // availability toggle
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};