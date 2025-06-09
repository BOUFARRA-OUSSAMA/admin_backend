<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Log;
use Exception;

class AppointmentObserver
{
    protected ReminderService $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Handle the Appointment "created" event.
     * Automatically schedule reminders for new appointments
     */
    public function created(Appointment $appointment): void
    {
        try {
            // Only schedule reminders for confirmed or scheduled appointments
            $validStatuses = ['confirmed', 'scheduled'];
            if (!in_array($appointment->status, $validStatuses)) {
                Log::info("Skipping reminder scheduling for appointment {$appointment->id} - status: {$appointment->status}");
                return;
            }

            // Check if appointment is in the future
            if ($appointment->appointment_datetime_start <= now()) {
                Log::info("Skipping reminder scheduling for appointment {$appointment->id} - appointment is in the past");
                return;
            }

            // Get default reminder settings for the patient
            $defaultSettings = $this->getDefaultReminderSettings($appointment);
            
            if (empty($defaultSettings['channels']) || empty($defaultSettings['reminder_times'])) {
                Log::info("Skipping reminder scheduling for appointment {$appointment->id} - no default settings configured");
                return;
            }

            // Schedule the reminders
            $result = $this->reminderService->scheduleReminders($appointment, [
                'channels' => $defaultSettings['channels'],
                'reminder_times' => $defaultSettings['reminder_times'],
                'priority' => $defaultSettings['priority'] ?? 'normal'
            ]);

            Log::info("Automatically scheduled reminders for appointment {$appointment->id}", [
                'scheduled_count' => $result['scheduled_count'] ?? 0,
                'channels' => $defaultSettings['channels'],
                'reminder_times' => $defaultSettings['reminder_times']
            ]);

        } catch (Exception $e) {
            Log::error("Failed to auto-schedule reminders for appointment {$appointment->id}: " . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Appointment "updated" event.
     * Reschedule reminders if appointment time changes
     */
    public function updated(Appointment $appointment): void
    {
        try {
            // Check if appointment datetime changed
            if (!$appointment->isDirty('appointment_datetime_start')) {
                return;
            }

            $oldDateTime = $appointment->getOriginal('appointment_datetime_start');
            $newDateTime = $appointment->appointment_datetime_start;

            Log::info("Appointment {$appointment->id} datetime changed from {$oldDateTime} to {$newDateTime}");

            // Cancel existing reminders for this appointment
            $this->reminderService->cancelAppointmentReminders($appointment->id, 'Appointment time changed');

            // If status is still confirmed and appointment is in future, reschedule
            if ($appointment->status === 'confirmed' && $newDateTime > now()) {
                $defaultSettings = $this->getDefaultReminderSettings($appointment);
                
                if (!empty($defaultSettings['channels']) && !empty($defaultSettings['reminder_times'])) {
                    $result = $this->reminderService->scheduleReminders($appointment, [
                        'channels' => $defaultSettings['channels'],
                        'reminder_times' => $defaultSettings['reminder_times'],
                        'priority' => $defaultSettings['priority'] ?? 'normal'
                    ]);

                    Log::info("Rescheduled reminders for appointment {$appointment->id} due to time change", [
                        'scheduled_count' => $result['scheduled_count'] ?? 0,
                        'old_datetime' => $oldDateTime,
                        'new_datetime' => $newDateTime
                    ]);
                }
            }

        } catch (Exception $e) {
            Log::error("Failed to handle appointment update for reminders {$appointment->id}: " . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Appointment "deleted" event.
     * Cancel all reminders for deleted appointments
     */
    public function deleted(Appointment $appointment): void
    {
        try {
            $this->reminderService->cancelAppointmentReminders($appointment->id, 'Appointment deleted');
            
            Log::info("Cancelled all reminders for deleted appointment {$appointment->id}");

        } catch (Exception $e) {
            Log::error("Failed to cancel reminders for deleted appointment {$appointment->id}: " . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle status changes that affect reminder scheduling
     */
    public function updating(Appointment $appointment): void
    {
        try {
            // Handle status changes
            if ($appointment->isDirty('status')) {
                $oldStatus = $appointment->getOriginal('status');
                $newStatus = $appointment->status;

                Log::info("Appointment {$appointment->id} status changing from {$oldStatus} to {$newStatus}");

                // If appointment is being cancelled or completed, cancel reminders
                if (in_array($newStatus, ['cancelled', 'completed', 'no_show'])) {
                    $this->reminderService->cancelAppointmentReminders(
                        $appointment->id, 
                        "Appointment status changed to {$newStatus}"
                    );

                    Log::info("Cancelled reminders for appointment {$appointment->id} due to status change to {$newStatus}");
                }
                
                // If appointment is being confirmed from another status, schedule reminders
                elseif ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
                    // Only if appointment is in the future
                    if ($appointment->appointment_datetime_start > now()) {
                        $defaultSettings = $this->getDefaultReminderSettings($appointment);
                        
                        if (!empty($defaultSettings['channels']) && !empty($defaultSettings['reminder_times'])) {
                            $result = $this->reminderService->scheduleReminders($appointment, [
                                'channels' => $defaultSettings['channels'],
                                'reminder_times' => $defaultSettings['reminder_times'],
                                'priority' => $defaultSettings['priority'] ?? 'normal'
                            ]);

                            Log::info("Scheduled reminders for appointment {$appointment->id} due to confirmation", [
                                'scheduled_count' => $result['scheduled_count'] ?? 0
                            ]);
                        }
                    }
                }
            }

        } catch (Exception $e) {
            Log::error("Failed to handle appointment status change for reminders {$appointment->id}: " . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get default reminder settings for an appointment
     */
    private function getDefaultReminderSettings(Appointment $appointment): array
    {
        try {
            // Get patient's reminder preferences
            $patient = $appointment->patient()->first();
            
            if ($patient && $patient->user) {
                $userSettings = $this->reminderService->getUserReminderSettings($patient->user->id);
                
                // Build enabled channels based on user preferences
                $enabledChannels = [];
                if ($userSettings['email_enabled'] ?? true) {
                    $enabledChannels[] = 'email';
                }
                if ($userSettings['sms_enabled'] ?? false) {
                    $enabledChannels[] = 'sms';
                }
                if ($userSettings['push_enabled'] ?? true) {
                    $enabledChannels[] = 'push';
                }
                if ($userSettings['in_app_enabled'] ?? true) {
                    $enabledChannels[] = 'in_app';
                }

                return [
                    'channels' => !empty($enabledChannels) ? $enabledChannels : ['email'], // Fallback to email
                    'reminder_times' => $userSettings['default_reminder_times'] ?? [60, 1440], // 1h and 24h default
                    'priority' => 'normal'
                ];
            }

            // Fallback defaults if no user settings found
            return [
                'channels' => ['email'],
                'reminder_times' => [60, 1440], // 1 hour and 24 hours before
                'priority' => 'normal'
            ];

        } catch (Exception $e) {
            Log::error("Failed to get default reminder settings for appointment {$appointment->id}: " . $e->getMessage());
            
            // Return safe defaults
            return [
                'channels' => ['email'],
                'reminder_times' => [60, 1440],
                'priority' => 'normal'
            ];
        }
    }
}
