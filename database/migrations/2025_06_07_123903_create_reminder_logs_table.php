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
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Patient or staff who triggered
            
            // Reminder details
            $table->enum('reminder_type', ['24h', '2h', 'manual', 'custom'])->default('24h');
            $table->enum('channel', ['email', 'push', 'sms', 'in_app'])->default('email');
            $table->enum('trigger_type', ['automatic', 'manual'])->default('automatic');
            
            // Scheduling and delivery
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('delivery_status', [
                'pending', 'sent', 'delivered', 'failed', 'bounced', 'cancelled'
            ])->default('pending');
            
            // Content and tracking
            $table->string('subject')->nullable();
            $table->text('message_content')->nullable();
            $table->string('job_id')->nullable(); // Laravel queue job ID
            $table->json('metadata')->nullable(); // Additional tracking data
            
            // Error handling
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            
            // User interaction tracking
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->string('tracking_token')->nullable();
            
            $table->timestamps();
            
            $table->index(['appointment_id', 'reminder_type']);
            $table->index(['user_id', 'sent_at']);
            $table->index(['delivery_status', 'scheduled_for']);
            $table->index('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
