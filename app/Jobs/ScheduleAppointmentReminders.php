<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\ReminderSetting;
use App\Models\ScheduledReminderJob;
use App\Jobs\SendAppointmentReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ScheduleAppointmentReminders implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    protected $appointmentId;
    protected $reminderSettings;

    /**
     * Create a new job instance.
     */
    public function __construct(int $appointmentId, array $reminderSettings = [])
    {
        $this->appointmentId = $appointmentId;
        $this->reminderSettings = $reminderSettings;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Scheduling reminders for appointment", [
                'appointment_id' => $this->appointmentId,
            ]);

            // Get appointment with relationships
            $appointment = Appointment::findOrFail($this->appointmentId);

            // Check if appointment is valid for scheduling reminders
            if (!$this->isAppointmentValidForScheduling($appointment)) {
                Log::info("Appointment not valid for scheduling reminders", [
                    'appointment_id' => $this->appointmentId,
                    'status' => $appointment->status,
                    'appointment_time' => $appointment->appointment_datetime_start,
                ]);
                return;
            }

            // Get user reminder settings or use provided settings
            $userSettings = $this->getUserReminderSettings($appointment->patient_user_id);

            // Schedule reminders based on settings
            $this->scheduleRemindersForAppointment($appointment, $userSettings);

            Log::info("Successfully scheduled reminders for appointment", [
                'appointment_id' => $this->appointmentId,
            ]);

        } catch (Exception $e) {
            Log::error("Failed to schedule reminders for appointment", [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Reminder scheduling job permanently failed", [
            'appointment_id' => $this->appointmentId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Check if appointment is valid for scheduling reminders
     */
    protected function isAppointmentValidForScheduling(Appointment $appointment): bool
    {
        // Don't schedule reminders for cancelled or completed appointments
        $invalidStatuses = ['cancelled', 'completed', 'no-show'];
        if (in_array($appointment->status, $invalidStatuses)) {
            return false;
        }

        // Don't schedule reminders for past appointments
        if ($appointment->appointment_datetime_start <= now()) {
            return false;
        }

        // Don't schedule if appointment is too soon (less than 30 minutes)
        if ($appointment->appointment_datetime_start->diffInMinutes(now()) < 30) {
            return false;
        }

        return true;
    }

    /**
     * Get user reminder settings
     */
    protected function getUserReminderSettings(int $userId): array
    {
        // Use provided settings or get from database
        if (!empty($this->reminderSettings)) {
            return $this->reminderSettings;
        }

        $reminderSetting = ReminderSetting::where('user_id', $userId)->first();
        
        if (!$reminderSetting) {
            $defaults = ReminderSetting::getDefaults('patient');
            $defaults['user_id'] = $userId;
            $reminderSetting = ReminderSetting::create($defaults);
        }

        return $reminderSetting->toArray();
    }

    /**
     * Schedule reminders for the appointment
     */
    protected function scheduleRemindersForAppointment(
        Appointment $appointment,
        array $userSettings
    ): void {
        // Cancel any existing scheduled reminders for this appointment
        $this->cancelExistingReminders($appointment->id);

        $appointmentTime = $appointment->appointment_datetime_start;
        
        // Get enabled channels from user settings
        $enabledChannels = [];
        if ($userSettings['email_enabled'] ?? false) $enabledChannels[] = 'email';
        if ($userSettings['push_enabled'] ?? false) $enabledChannels[] = 'push';
        if ($userSettings['sms_enabled'] ?? false) $enabledChannels[] = 'sms';

        if (empty($enabledChannels)) {
            Log::info("No enabled channels for user, skipping reminders", [
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->patient_user_id,
            ]);
            return;
        }

        // Get reminder timings from user settings
        $reminderTimings = [];
        if ($userSettings['reminder_24h_enabled'] ?? true) {
            $reminderTimings[] = $userSettings['first_reminder_hours'] ?? 24;
        }
        if ($userSettings['reminder_2h_enabled'] ?? true) {
            $reminderTimings[] = $userSettings['second_reminder_hours'] ?? 2;
        }

        // Schedule reminders based on timings
        foreach ($reminderTimings as $hours) {
            $reminderTime = $appointmentTime->copy()->subHours($hours);
            
            if ($reminderTime->isFuture()) {
                $reminderType = $this->getReminderType($hours);
                
                foreach ($enabledChannels as $channel) {
                    $this->scheduleIndividualReminder(
                        $appointment,
                        $reminderType,
                        $channel,
                        $reminderTime
                    );
                }
            }
        }

        // Schedule custom reminders if configured
        if (isset($userSettings['custom_settings']['custom_reminders'])) {
            $this->scheduleCustomReminders(
                $appointment,
                $userSettings['custom_settings']['custom_reminders'],
                $enabledChannels,
                $appointmentTime
            );
        }
    }

    /**
     * Get reminder type based on hours
     */
    protected function getReminderType(int $hours): string
    {
        return match ($hours) {
            24 => '24h',
            2 => '2h',
            1 => '1h',
            default => 'custom'
        };
    }

    /**
     * Cancel existing reminders for an appointment
     */
    protected function cancelExistingReminders(int $appointmentId): void
    {
        $existingJobs = ScheduledReminderJob::where('appointment_id', $appointmentId)
            ->where('status', 'pending')
            ->get();

        foreach ($existingJobs as $job) {
            $job->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'failure_reason' => 'rescheduled'
            ]);
        }
    }

    /**
     * Schedule an individual reminder
     */
    protected function scheduleIndividualReminder(
        Appointment $appointment,
        string $reminderType,
        string $channel,
        Carbon $scheduledFor
    ): void {
        try {
            // Create the reminder job with updated constructor
            $reminderData = [
                'type' => $reminderType,
                'scheduled_for' => $scheduledFor->toISOString(),
                'priority' => 'normal'
            ];

            // Calculate delay
            $delay = $scheduledFor->diffInSeconds(now());
            if ($delay <= 0) {
                Log::warning("Cannot schedule reminder in the past", [
                    'appointment_id' => $appointment->id,
                    'scheduled_for' => $scheduledFor,
                    'reminder_type' => $reminderType,
                    'channel' => $channel,
                ]);
                return;
            }

            // Dispatch the job with delay
            $job = SendAppointmentReminder::dispatch(
                $appointment->id,
                $appointment->patient_user_id,
                $channel,
                $reminderType,
                $reminderData
            )->delay($delay);

            // Generate a unique identifier for tracking
            $trackingId = 'reminder_' . $appointment->id . '_' . $reminderType . '_' . $channel . '_' . time();

            // Record the scheduled job
            ScheduledReminderJob::create([
                'appointment_id' => $appointment->id,
                'queue_job_id' => $trackingId, // Use tracking ID instead of job ID
                'reminder_type' => $reminderType,
                'channel' => $channel,
                'scheduled_for' => $scheduledFor,
                'status' => 'pending',
                'job_payload' => [
                    'appointment_id' => $appointment->id,
                    'user_id' => $appointment->patient_user_id,
                    'reminder_type' => $reminderType,
                    'channel' => $channel,
                    'reminder_data' => $reminderData,
                    'tracking_id' => $trackingId
                ],
            ]);

            Log::info("Scheduled individual reminder", [
                'appointment_id' => $appointment->id,
                'reminder_type' => $reminderType,
                'channel' => $channel,
                'scheduled_for' => $scheduledFor,
                'tracking_id' => $trackingId,
            ]);

        } catch (Exception $e) {
            Log::error("Failed to schedule individual reminder", [
                'appointment_id' => $appointment->id,
                'reminder_type' => $reminderType,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Schedule custom reminders
     */
    protected function scheduleCustomReminders(
        Appointment $appointment,
        array $customReminders,
        array $enabledChannels,
        Carbon $appointmentTime
    ): void {
        foreach ($customReminders as $customReminder) {
            if (!isset($customReminder['hours_before'])) continue;

            $reminderTime = $appointmentTime->copy()->subHours($customReminder['hours_before']);
            
            if ($reminderTime->isFuture()) {
                $channels = $customReminder['channels'] ?? $enabledChannels;
                
                foreach ($channels as $channel) {
                    if (in_array($channel, $enabledChannels)) {
                        $this->scheduleIndividualReminder(
                            $appointment,
                            'custom',
                            $channel,
                            $reminderTime
                        );
                    }
                }
            }
        }
    }
}
