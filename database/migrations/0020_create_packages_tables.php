<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Introduces the dedicated packages table and relational pivots,
     * supporting both standard clinical bundles and organization-authored packages.
     */
    public function up(): void
    {
        // 1. Dedicated Packages Table
        Schema::create('packages', function (Blueprint $table) {
            $table->id();

            // Nullable link to a recognized organization account.
            // NULL = Medscreen Global Public Package; Non-null = Private Organization Package
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('name', 100);
            $table->decimal('price', 8, 2);
            $table->text('description')->nullable();
            $table->text('preparation')->nullable();
            $table->unsignedInteger('estimated_time')->nullable(); // Duration in minutes
            $table->enum('gender_restriction', ['male', 'female', 'both'])->default('both');
            $table->boolean('is_available')->default(true);
            $table->enum('status', ['pending', 'approved', 'declined', 'disabled'])->default('pending');
            $table->text('status_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint prevents duplicate package names within the same organization or globally
            $table->unique(['organization_id', 'name'], 'package_org_name_unique');
        });

        // 2. Pivot Table: Maps which individual services compose each package
        Schema::create('package_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')
                ->constrained('packages')
                ->onDelete('cascade');
            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');
            $table->timestamps();

            // Compound unique constraint prevents adding the same service twice to a package
            $table->unique(['package_id', 'service_id'], 'package_service_unique');
        });

        // 3. Pivot Table: Maps booked packages to appointments
        Schema::create('appointment_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                ->constrained('appointments')
                ->onDelete('cascade');
            $table->foreignId('package_id')
                ->constrained('packages')
                ->onDelete('cascade');
            $table->timestamps();

            // Compound unique constraint prevents duplicate package bookings on the same appointment
            $table->unique(['appointment_id', 'package_id'], 'appointment_package_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_package');
        Schema::dropIfExists('package_service');
        Schema::dropIfExists('packages');
    }
};