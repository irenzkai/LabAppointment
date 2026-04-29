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
        Schema::create('laboratory_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // 'none', 'pending_patient' (staff asked), 'pending_staff' (patient asked), 'granted'
            $table->string('permission_status')->default('none');
            $table->json('dynamic_data')->nullable(); // Stores {headers: [], rows: []}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_histories');
    }
};
