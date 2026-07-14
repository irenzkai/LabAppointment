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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // 1. Split Name Fields (1NF Atomic)
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            // 2. Profile Details
            $table->date('birthdate');
            $table->string('sex'); // Male/Female

            // 3. Split Address Fields (3NF Atomic - PSGC API Compatible)
            $table->string('street');
            $table->string('barangay');
            $table->string('city');
            $table->string('province');

            // 4. Contact & Security
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('password');

            // 5. System Flags
            $table->string('role')->default('user'); // user, staff, lab_tech, admin
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};