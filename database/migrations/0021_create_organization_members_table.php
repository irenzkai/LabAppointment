<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores reusable pre-recorded rosters of people for recognized organizations.
     */
    public function up(): void
    {
        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();

            // Foreign key linking directly to the organization user account owner
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Roster group categorization (e.g. "OJT STUDENTS", "REGULAR EMPLOYEES", "BATCH 2026")
            $table->string('group_name', 100)->index();

            // 1. Member Demographics (1NF Atomic)
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->nullable();
            $table->string('last_name', 60);
            $table->string('suffix', 10)->nullable();
            $table->date('birthdate')->nullable();
            $table->enum('sex', ['Male', 'Female'])->nullable();

            // 2. Member Contact Details (Optional for recognized corporate/school entities)
            $table->string('phone', 11)->nullable();
            $table->string('email', 191)->nullable();

            // 3. Member Address Details (3NF Atomic - PSGC API Compatible)
            $table->string('street', 150)->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();

            // 4. Pre-configured Diagnostic Test Assignment (Optional Reusable Presets)
            $table->foreignId('default_package_id')
                ->nullable()
                ->constrained('packages')
                ->onDelete('set null');

            $table->json('default_service_ids')->nullable(); // Array of service IDs for automated test mapping

            $table->timestamps();
            $table->softDeletes();

            // Composite index to optimize rapid roster category loading
            $table->index(['user_id', 'group_name'], 'org_user_group_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_members');
    }
};