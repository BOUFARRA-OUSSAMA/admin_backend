<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\User;
use App\Models\ReminderLog;
use App\Models\ScheduledReminderJob;
use App\Models\ReminderAnalytics;
use App\Services\ReminderNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SendAppointmentReminder implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    protected $appointmentId;
    protected $userId;
    protected $channel;
    protected $reminderType;
    protected $reminderData;
    protected $reminderLogId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $appointmentId,
        int $userId,
        string $channel,
        string $reminderType,
        array $reminderData = []
    ) {
        $this->appointmentId = $appointmentId;
        $this->userId = $userId;
        $this->channel = $channel;
        $this->reminderType = $reminderType;
        $this->reminderData = $reminderData;
        $this->reminderLogId = null; // Initialize as null
    }

    /**
     * Execute the job.
     */
    public function handle(ReminderNotificationService $notificationService): void
    {
        try {
            // Generate tracking ID for this reminder job
            $trackingId = 'reminder_' . $this->appointmentId . '_' . $this->userId . '_' . $this->reminderType . '_' . $this->channel . '_' . time();
            
            Log::info("Processing reminder job", [
                'appointment_id' => $this->appointmentId,
                'user_id' => $this->userId,
                'reminder_type' => $this->reminderType,
                'channel' => $this->channel,
                'tracking_id' => $trackingId,
            ]);

            // Get appointment with relationships, including soft-deleted ones
            $appointment = Appointment::withTrashed()
                ->with(['patient', 'doctor'])
                ->find($this->appointmentId);
                
            // Check if appointment exists
            if (!$appointment) {
                Log::warning("Appointment not found for reminder sending", [
                    'appointment_id' => $this->appointmentId,
                    'user_id' => $this->userId,
                    'tracking_id' => $trackingId,
                ]);
                return;
            }
            
            // Check if appointment is soft-deleted
            if ($appointment->trashed()) {
                Log::info("Skipping reminder for soft-deleted appointment", [
                    'appointment_id' => $this->appointmentId,
                    'user_id' => $this->userId,
                    'deleted_at' => $appointment->deleted_at,
                    'tracking_id' => $trackingId,
                ]);
                $this->markJobAsCancelled('Appointment was soft-deleted');
                return;
            }
                
            // Get the user for this reminder
            $user = User::findOrFail($this->userId);

            // Check if appointment is still valid for reminders
            if (!$this->isAppointmentValidForReminder($appointment)) {
                $this->markJobAsCancelled('Appointment no longer valid for reminders');
                return;
            }

            // Get or create reminder log
            $reminderLog = $this->getOrCreateReminderLog($appointment);

            // Get the user
            $user = User::find($this->userId);
            if (!$user) {
                throw new \Exception("User not found: {$this->userId}");
            }

            // Update scheduled job status
            $this->updateScheduledJobStatus('processing');

            // Send the reminder
            $result = $notificationService->sendReminder(
                $appointment,
                $user,
                $this->channel,
                $this->reminderType,
                $this->reminderData
            );

            if ($result['success']) {
                $this->handleSuccessfulSend($reminderLog, $result);
            } else {
                $this->handleFailedSend($reminderLog, $result['error']);
            }

        } catch (Exception $e) {
            $trackingId = 'reminder_' . $this->appointmentId . '_' . $this->userId . '_' . $this->reminderType . '_' . $this->channel . '_' . time();
            
            Log::error("Reminder job failed", [
                'appointment_id' => $this->appointmentId,
                'reminder_type' => $this->reminderType,
                'channel' => $this->channel,
                'error' => $e->getMessage(),
                'tracking_id' => $trackingId,
            ]);

            $this->handleJobFailure($e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Reminder job permanently failed", [
            'appointment_id' => $this->appointmentId,
            'reminder_type' => $this->reminderType,
            'channel' => $this->channel,
            'error' => $exception->getMessage(),
        ]);

        $this->handleJobFailure($exception->getMessage());
    }

    /**
     * Check if appointment is still valid for reminders
     */
    protected function isAppointmentValidForReminder(Appointment $appointment): bool
    {
        // Don't send reminders for cancelled or completed appointments
        if (in_array($appointment->status, [
            Appointment::STATUS_CANCELLED_BY_PATIENT,
            Appointment::STATUS_CANCELLED_BY_CLINIC,
            Appointment::STATUS_COMPLETED,
        ])) {
            return false;
        }

        // Don't send reminders for past appointments
        if ($appointment->appointment_datetime_start->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get or create reminder log
     */
    protected function getOrCreateReminderLog(Appointment $appointment): ReminderLog
    {
        if ($this->reminderLogId) {
            return ReminderLog::findOrFail($this->reminderLogId);
        }

        $trackingId = 'reminder_' . $appointment->id . '_' . $this->reminderType . '_' . $this->channel . '_' . time();
        
        return ReminderLog::create([
            'appointment_id' => $appointment->id,
            'user_id' => $appointment->patient_user_id,
            'reminder_type' => $this->reminderType,
            'channel' => $this->channel,
            'trigger_type' => 'automatic',
            'scheduled_for' => now(),
            'job_id' => $trackingId,
            'delivery_status' => 'pending',
        ]);
    }

    /**
     * Update scheduled job status
     */
    protected function updateScheduledJobStatus(string $status): void
    {
        $trackingId = 'reminder_' . $this->appointmentId . '_' . $this->reminderType . '_' . $this->channel;
        
        ScheduledReminderJob::where('queue_job_id', 'LIKE', $trackingId . '%')
            ->update([
                'status' => $status,
                'last_attempted_at' => now(),
                'attempts' => $this->attempts(),
            ]);
    }

    /**
     * Handle successful reminder send
     */
    protected function handleSuccessfulSend(ReminderLog $reminderLog, array $result): void
    {
        // Update reminder log
        $reminderLog->markAsSent();
        
        if (isset($result['tracking_token'])) {
            $reminderLog->update(['tracking_token' => $result['tracking_token']]);
        }

        // Update scheduled job
        $this->updateScheduledJobStatus('sent');

        // Update analytics
        $this->updateAnalytics('sent');

        Log::info("Reminder sent successfully", [
            'appointment_id' => $this->appointmentId,
            'reminder_type' => $this->reminderType,
            'channel' => $this->channel,
            'reminder_log_id' => $reminderLog->id,
        ]);
    }

    /**
     * Handle failed reminder send
     */
    protected function handleFailedSend(ReminderLog $reminderLog, string $error): void
    {
        $reminderLog->markAsFailed($error);
        $this->updateAnalytics('failed');

        if ($this->attempts() >= $this->tries) {
            $this->updateScheduledJobStatus('failed');
        }
    }

    /**
     * Handle job failure
     */
    protected function handleJobFailure(string $error): void
    {
        if ($this->reminderLogId) {
            $reminderLog = ReminderLog::find($this->reminderLogId);
            if ($reminderLog) {
                $reminderLog->markAsFailed($error);
            }
        }

        ScheduledReminderJob::where('queue_job_id', 'LIKE', 'reminder_' . $this->appointmentId . '_' . $this->reminderType . '_' . $this->channel . '%')
            ->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $error,
                'attempts' => $this->attempts(),
            ]);

        $this->updateAnalytics('failed');
    }

    /**
     * Mark job as cancelled
     */
    protected function markJobAsCancelled(string $reason): void
    {
        ScheduledReminderJob::where('queue_job_id', 'LIKE', 'reminder_' . $this->appointmentId . '_' . $this->reminderType . '_' . $this->channel . '%')
            ->update([
                'status' => 'cancelled',
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'failure_reason' => $reason,
            ]);

        Log::info("Reminder job cancelled", [
            'appointment_id' => $this->appointmentId,
            'reason' => $reason,
        ]);
    }

    /**
     * Update analytics
     */
    protected function updateAnalytics(string $status): void
    {
        try {
            $appointment = Appointment::find($this->appointmentId);
            if (!$appointment) return;

            $analytics = ReminderAnalytics::getOrCreateForDate(
                now(),
                $appointment->doctor_user_id
            );

            $analytics->incrementReminder($this->channel, $status);
        } catch (Exception $e) {
            Log::warning("Failed to update analytics", [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
