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

            // 1. Split Name Fields (1NF Atomic) with strict limits
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->nullable(); // Nullable per PSA guidelines
            $table->string('last_name', 60);
            $table->string('suffix', 10)->nullable(); // Aligned with the newly introduced suffix field

            // 2. Profile Details
            $table->date('birthdate');
            $table->enum('sex', ['Male', 'Female']); // PSA Standard binary classification

            // 3. Split Address Fields (3NF Atomic - PSGC API Compatible)
            $table->string('street', 150);
            $table->string('barangay', 100);
            $table->string('city', 100);       // City or Municipality
            $table->string('province', 100);

            // 4. Contact & Security
            $table->string('email', 191)->unique(); // Constrained unique index limit
            $table->string('phone', 11);            // Strictly 11-digit PH mobile format (09XXXXXXXXX)
            $table->string('password', 255);

            // 5. System Flags & Permissions
            $table->enum('role', ['user', 'staff', 'lab_tech', 'admin'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();

            // 6. Audit & Data Retention Compliance [102]
            $table->boolean('password_change_required')->default(false); // Force temporary password updates
            $table->softDeletes(); // Compliant deactivations

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->onDelete('cascade');
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
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};