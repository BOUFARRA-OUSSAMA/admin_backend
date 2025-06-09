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
        Schema::create('reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('user_type', ['patient', 'doctor', 'global'])->default('patient');
            
            // Reminder timing preferences
            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            
            // Timing settings (in hours before appointment)
            $table->integer('first_reminder_hours')->default(24); // 24 hours before
            $table->integer('second_reminder_hours')->default(2);  // 2 hours before
            $table->boolean('reminder_24h_enabled')->default(true);
            $table->boolean('reminder_2h_enabled')->default(true);
            
            // Preferred communication channels
            $table->json('preferred_channels')->nullable(); // ['email', 'push', 'sms']
            $table->string('timezone')->default('UTC');
            
            // Custom settings
            $table->json('custom_settings')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->unique(['user_id', 'user_type']);
            $table->index(['user_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_settings');
    }
};
