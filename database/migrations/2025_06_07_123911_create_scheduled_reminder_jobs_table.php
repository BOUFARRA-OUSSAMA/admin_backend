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
        Schema::create('scheduled_reminder_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('reminder_log_id')->nullable()->constrained('reminder_logs')->onDelete('cascade');
            
            // Job tracking
            $table->string('job_id')->unique(); // Laravel queue job ID
            $table->string('queue_job_id')->nullable(); // Queue job ID for tracking
            $table->enum('reminder_type', ['24h', '2h', 'manual', 'custom'])->default('24h');
            $table->enum('channel', ['email', 'sms', 'push'])->default('email');
            $table->timestamp('scheduled_for');
            
            // Status tracking
            $table->enum('status', [
                'pending', 'processing', 'sent', 'failed', 'cancelled', 'expired'
            ])->default('pending');
            
            // Retry logic
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('executed_at')->nullable(); // Add executed_at field
            $table->timestamp('failed_at')->nullable();
            
            // Metadata
            $table->json('job_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['appointment_id', 'reminder_type']);
            $table->index(['status', 'scheduled_for']);
            $table->index(['job_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reminder_jobs');
    }
};
