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
        Schema::create('notifications', function (Blueprint $table) {
            // 1. Primary UUID Key
            $table->uuid('id')->primary();
            
            // 2. Notification Parameters
            $table->string('type', 191); // Explicitly sized for database index safety
            $table->morphs('notifiable'); // Creates compound index (notifiable_type, notifiable_id)
            $table->text('data');         // JSON payload containing notification variables

            // 3. Read/Unread State Tracking
            $table->timestamp('read_at')->nullable()->index(); // Indexed to optimize count(unread) queries
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};