<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\ScheduledReminderJob;
use App\Models\ReminderLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class CancelScheduledReminders implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    private $appointmentId;
    private $reason;

    /**
     * Create a new job instance.
     */
    public function __construct(int $appointmentId, string $reason = 'appointment_cancelled')
    {
        $this->appointmentId = $appointmentId;
        $this->reason = $reason;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $appointment = Appointment::find($this->appointmentId);
            if (!$appointment) {
                Log::warning("Appointment not found for cancellation", [
                    'appointment_id' => $this->appointmentId
                ]);
                return;
            }

            // Get all pending scheduled reminder jobs for this appointment
            $scheduledJobs = ScheduledReminderJob::where('appointment_id', $this->appointmentId)
                ->where('status', 'pending')
                ->get();

            $cancelledCount = 0;
            $failedCount = 0;

            foreach ($scheduledJobs as $scheduledJob) {
                try {
                    // Cancel the queue job if it exists
                    if ($scheduledJob->queue_job_id) {
                        // Note: Laravel doesn't provide a direct way to cancel queued jobs
                        // This depends on your queue driver. For database/redis, you might need
                        // to implement custom logic or use packages like laravel-horizon
                        $this->cancelQueueJob($scheduledJob->queue_job_id);
                    }

                    // Update the scheduled job status
                    $scheduledJob->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'failure_reason' => $this->reason
                    ]);

                    // Create a log entry for the cancellation
                    ReminderLog::create([
                        'appointment_id' => $this->appointmentId,
                        'user_id' => $appointment->user_id,
                        'reminder_type' => $scheduledJob->reminder_type,
                        'channel' => $scheduledJob->channel,
                        'status' => 'cancelled',
                        'scheduled_for' => $scheduledJob->scheduled_for,
                        'cancelled_at' => now(),
                        'metadata' => [
                            'cancellation_reason' => $this->reason,
                            'original_job_id' => $scheduledJob->id,
                            'cancelled_by_job' => true
                        ]
                    ]);

                    $cancelledCount++;

                    Log::info("Cancelled scheduled reminder", [
                        'appointment_id' => $this->appointmentId,
                        'scheduled_job_id' => $scheduledJob->id,
                        'reminder_type' => $scheduledJob->reminder_type,
                        'channel' => $scheduledJob->channel,
                        'reason' => $this->reason
                    ]);

                } catch (\Exception $e) {
                    $failedCount++;
                    
                    Log::error("Failed to cancel scheduled reminder", [
                        'appointment_id' => $this->appointmentId,
                        'scheduled_job_id' => $scheduledJob->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Mark as failed but don't throw to continue with other jobs
                    $scheduledJob->update([
                        'status' => 'failed',
                        'failure_reason' => 'cancellation_failed: ' . $e->getMessage()
                    ]);
                }
            }

            // Update appointment metadata if needed
            $this->updateAppointmentMetadata($appointment, $cancelledCount, $failedCount);

            Log::info("Completed reminder cancellation process", [
                'appointment_id' => $this->appointmentId,
                'total_jobs' => $scheduledJobs->count(),
                'cancelled' => $cancelledCount,
                'failed' => $failedCount,
                'reason' => $this->reason
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to cancel scheduled reminders", [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Cancel a queue job based on the queue driver
     */
    private function cancelQueueJob(string $jobId): void
    {
        try {
            // For database queue driver
            if (config('queue.default') === 'database') {
                \DB::table('jobs')
                    ->where('id', $jobId)
                    ->delete();
            }
            
            // For Redis queue driver (requires predis/predis or phpredis)
            elseif (config('queue.default') === 'redis') {
                // This is a simplified approach - you might need more sophisticated logic
                // depending on your Redis queue configuration
                $redis = app('redis');
                $redis->del("queues:default:$jobId");
            }
            
            // For other queue drivers, you might need custom implementation
            // or use packages like laravel-horizon for advanced job management
            
        } catch (\Exception $e) {
            Log::warning("Could not cancel queue job directly", [
                'job_id' => $jobId,
                'driver' => config('queue.default'),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update appointment metadata with cancellation info
     */
    private function updateAppointmentMetadata(Appointment $appointment, int $cancelledCount, int $failedCount): void
    {
        try {
            $metadata = $appointment->metadata ?? [];
            $metadata['reminder_cancellation'] = [
                'cancelled_at' => now()->toISOString(),
                'reason' => $this->reason,
                'cancelled_reminders' => $cancelledCount,
                'failed_cancellations' => $failedCount,
                'cancelled_by_job' => static::class
            ];

            $appointment->update(['metadata' => $metadata]);
        } catch (\Exception $e) {
            Log::warning("Failed to update appointment metadata after reminder cancellation", [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("CancelScheduledReminders job failed", [
            'appointment_id' => $this->appointmentId,
            'reason' => $this->reason,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Optionally, you could implement notification to administrators
        // or retry logic here
    }
}
