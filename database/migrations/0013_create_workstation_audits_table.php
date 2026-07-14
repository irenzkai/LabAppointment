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
        Schema::create('workstation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_result_id')->constrained()->onDelete('cascade');
            $table->string('workstation_type'); // 'lab', 'med', 'radio', 'drug'

            // V1 Encoder Audit
            $table->foreignId('v1_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('v1_by_name')->nullable();
            $table->timestamp('v1_at')->nullable();

            // V2 Verifier Audit
            $table->foreignId('v2_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('v2_by_name')->nullable();
            $table->timestamp('v2_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workstation_audits');
    }
};