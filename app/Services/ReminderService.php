<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Models\ReminderSetting;
use App\Models\ReminderLog;
use App\Models\ScheduledReminderJob;
use App\Models\ReminderAnalytics;
use App\Jobs\ScheduleAppointmentReminders;
use App\Jobs\CancelScheduledReminders;
use App\Jobs\SendAppointmentReminder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReminderService
{
    /**
     * Schedule reminders for an appointment
     */
    public function scheduleReminders(Appointment $appointment, array $customSettings = []): array
    {
        try {
            DB::beginTransaction();

            // Get user's reminder settings or create defaults
            $reminderSettings = $this->getUserReminderSettings($appointment->patient_user_id);
            
            // Apply any custom settings
            if (!empty($customSettings)) {
                $reminderSettings = $this->applyCustomSettings($reminderSettings, $customSettings);
            }

            // Validate that the appointment is in the future
            if ($appointment->appointment_datetime_start <= now()) {
                throw new \InvalidArgumentException('Cannot schedule reminders for past appointments');
            }

            // Cancel any existing reminders for this appointment
            $this->cancelExistingReminders($appointment->id, 'rescheduled');

            // Dispatch the scheduling job
            $job = ScheduleAppointmentReminders::dispatch($appointment->id, $reminderSettings);            Log::info("Reminder scheduling initiated", [
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->patient_user_id,
                'appointment_date' => $appointment->appointment_datetime_start,
                'settings' => $reminderSettings
            ]);            // Generate tracking ID for this job
            $trackingId = 'reminder_schedule_' . $appointment->id . '_' . time();
            
            // Calculate scheduled count and other response data
            $reminderTimes = $customSettings['reminder_times'] ?? $this->getDefaultReminderTimes($reminderSettings);
            $channels = $customSettings['channels'] ?? $this->getEnabledChannels($reminderSettings);
            $scheduledCount = count($reminderTimes) * count($channels);
            
            DB::commit();

            return [
                'success' => true,
                'message' => 'Reminders scheduled successfully',
                'appointment_id' => $appointment->id,
                'settings_applied' => $reminderSettings,
                'tracking_id' => $trackingId,
                'scheduled_count' => $scheduledCount,
                'reminder_times' => $reminderTimes,
                'channels' => $channels
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to schedule reminders", [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to schedule reminders: ' . $e->getMessage(),
                'appointment_id' => $appointment->id
            ];
        }
    }

    /**
     * Cancel all reminders for an appointment
     */
    public function cancelReminders(int $appointmentId, string $reason = 'manual_cancellation'): array
    {
        try {
            $appointment = Appointment::find($appointmentId);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            // Dispatch the cancellation job
            $job = CancelScheduledReminders::dispatch($appointmentId, $reason);

            Log::info("Reminder cancellation initiated", [
                'appointment_id' => $appointmentId,
                'reason' => $reason
            ]);            // Generate tracking ID for cancellation job
            $trackingId = 'cancel_reminder_' . $appointmentId . '_' . time();
            
            return [
                'success' => true,
                'message' => 'Reminder cancellation initiated',
                'appointment_id' => $appointmentId,
                'reason' => $reason,
                'tracking_id' => $trackingId
            ];

        } catch (\Exception $e) {
            Log::error("Failed to cancel reminders", [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel reminders: ' . $e->getMessage(),
                'appointment_id' => $appointmentId
            ];
        }
    }

    /**
     * Send an immediate reminder (manual override) - supports single channel
     */
    public function sendImmediateReminder(int $appointmentId, string $channel, array $options = []): array
    {
        try {
            $appointment = Appointment::find($appointmentId);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            // Validate channel
            $validChannels = ['email', 'sms', 'push', 'in_app'];
            if (!in_array($channel, $validChannels)) {
                throw new \InvalidArgumentException('Invalid notification channel');
            }

            // Prepare reminder data
            $reminderData = [
                'type' => $options['type'] ?? 'manual',
                'custom_message' => $options['message'] ?? null,
                'include_attachment' => $options['include_attachment'] ?? false,
                'priority' => $options['priority'] ?? 'normal'
            ];

            // Dispatch the reminder job immediately
            $job = SendAppointmentReminder::dispatch(
                $appointment->id,
                $appointment->patient_user_id,
                $channel,
                'manual',
                $reminderData
            );

            Log::info("Immediate reminder sent", [
                'appointment_id' => $appointmentId,
                'user_id' => $appointment->patient_user_id,
                'channel' => $channel,
                'options' => $options
            ]);

            // Generate tracking ID for immediate reminder
            $trackingId = 'immediate_reminder_' . $appointmentId . '_' . $channel . '_' . time();
            
            return [
                'success' => true,
                'message' => 'Immediate reminder sent',
                'appointment_id' => $appointmentId,
                'channel' => $channel,
                'tracking_id' => $trackingId
            ];

        } catch (\Exception $e) {
            Log::error("Failed to send immediate reminder", [
                'appointment_id' => $appointmentId,
                'channel' => $channel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send immediate reminder: ' . $e->getMessage(),
                'appointment_id' => $appointmentId
            ];
        }
    }

    /**
     * Send immediate reminder for testing purposes - supports multiple channels
     */
    public function sendTestReminder(Appointment $appointment, array $channels, ?string $customMessage = null, bool $isTest = false): array
    {
        try {
            $sentChannels = [];
            $failedChannels = [];
            
            foreach ($channels as $channel) {
                try {
                    // Dispatch immediate reminder job with correct parameters
                    $job = SendAppointmentReminder::dispatch(
                        $appointment->id,                                    // appointmentId
                        $appointment->patient_user_id,                      // userId
                        $channel,                                           // channel
                        'test',                                            // reminderType
                        [                                                  // reminderData
                            'custom_message' => $customMessage ?? 'Test reminder for your upcoming appointment',
                            'test_mode' => $isTest,
                            'priority' => 'normal'
                        ]
                    );
                    
                    $sentChannels[] = $channel;
                    
                    // Log the test reminder
                    $this->logReminder($appointment->id, $channel, 'test_reminder', 'sent', [
                        'message' => $customMessage,
                        'sent_at' => now()
                    ]);
                    
                } catch (\Exception $e) {
                    $failedChannels[] = [
                        'channel' => $channel,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error("Failed to send test reminder", [
                        'appointment_id' => $appointment->id,
                        'channel' => $channel,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return [
                'sent_channels' => $sentChannels,
                'failed_channels' => $failedChannels,
                'total_sent' => count($sentChannels),
                'total_failed' => count($failedChannels)
            ];
            
        } catch (\Exception $e) {
            Log::error("Critical error in sendImmediateReminder", [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get reminder status for an appointment
     */
    public function getReminderStatus(int $appointmentId): array
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) {
            throw new \InvalidArgumentException('Appointment not found');
        }

        // Get scheduled reminders
        $scheduledReminders = ScheduledReminderJob::where('appointment_id', $appointmentId)
            ->orderBy('scheduled_for', 'asc')
            ->get();

        // Get reminder logs
        $reminderLogs = ReminderLog::where('appointment_id', $appointmentId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $stats = [
            'total_scheduled' => $scheduledReminders->count(),
            'pending' => $scheduledReminders->where('status', 'pending')->count(),
            'sent' => $scheduledReminders->where('status', 'completed')->count(),
            'failed' => $scheduledReminders->where('status', 'failed')->count(),
            'cancelled' => $scheduledReminders->where('status', 'cancelled')->count(),
            'total_sent' => $reminderLogs->where('delivery_status', 'sent')->count(),
            'delivery_rate' => $this->calculateDeliveryRate($appointmentId)
        ];

        return [
            'appointment_id' => $appointmentId,
            'appointment_date' => $appointment->appointment_date,
            'statistics' => $stats,
            'scheduled_reminders' => $scheduledReminders->map(function ($reminder) {
                return [
                    'id' => $reminder->id,
                    'type' => $reminder->reminder_type,
                    'channel' => $reminder->channel,
                    'scheduled_for' => $reminder->scheduled_for,
                    'status' => $reminder->status,
                    'created_at' => $reminder->created_at,
                    'executed_at' => $reminder->executed_at,
                    'failure_reason' => $reminder->failure_reason
                ];
            }),
            'recent_logs' => $reminderLogs->take(10)->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->reminder_type,
                    'channel' => $log->channel,
                    'status' => $log->delivery_status,
                    'sent_at' => $log->sent_at,
                    'delivered_at' => $log->delivered_at,
                    'error_message' => $log->error_message
                ];
            })
        ];
    }

    /**
     * Update reminder settings for a user
     */
    public function updateUserReminderSettings(int $userId, array $settings): array
    {
        try {
            DB::beginTransaction();            $defaults = ReminderSetting::getDefaults('patient');
            $defaults['user_id'] = $userId;
            
            $reminderSetting = ReminderSetting::firstOrCreate(
                ['user_id' => $userId],
                $defaults
            );

            // Validate and apply settings
            $validatedSettings = $this->validateReminderSettings($settings);
            $reminderSetting->update($validatedSettings);

            DB::commit();

            Log::info("Reminder settings updated", [
                'user_id' => $userId,
                'settings' => $validatedSettings
            ]);

            return [
                'success' => true,
                'message' => 'Reminder settings updated successfully',
                'settings' => $reminderSetting->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to update reminder settings", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);            return [
                'success' => false,
                'message' => 'Failed to update reminder settings: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get reminder analytics for a user or system-wide
     */
    public function getReminderAnalytics(?int $userId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $query = ReminderAnalytics::whereBetween('analytics_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $analytics = $query->get();

        // Aggregate the data
        $aggregated = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'totals' => [
                'reminders_sent' => $analytics->sum('reminders_sent'),
                'reminders_delivered' => $analytics->sum('reminders_delivered'),
                'reminders_failed' => $analytics->sum('reminders_failed'),
                'unique_users' => $analytics->pluck('user_id')->unique()->count(),
                'appointments_with_reminders' => $analytics->sum('appointments_with_reminders')
            ],
            'rates' => [
                'delivery_rate' => 0,
                'failure_rate' => 0
            ],
            'by_channel' => [],
            'daily_breakdown' => $analytics->groupBy('analytics_date')->map(function ($dayData) {
                return [
                    'date' => $dayData->first()->analytics_date,
                    'sent' => $dayData->sum('reminders_sent'),
                    'delivered' => $dayData->sum('reminders_delivered'),
                    'failed' => $dayData->sum('reminders_failed')
                ];
            })->values()
        ];

        // Calculate rates
        if ($aggregated['totals']['reminders_sent'] > 0) {
            $aggregated['rates']['delivery_rate'] = round(
                ($aggregated['totals']['reminders_delivered'] / $aggregated['totals']['reminders_sent']) * 100, 
                2
            );
            $aggregated['rates']['failure_rate'] = round(
                ($aggregated['totals']['reminders_failed'] / $aggregated['totals']['reminders_sent']) * 100, 
                2
            );
        }

        // Channel breakdown
        $channelStats = ReminderLog::whereBetween('created_at', [$startDate, $endDate])
            ->when($userId, function ($query) use ($userId) {
                return $query->where('user_id', $userId);
            })
            ->groupBy('channel')
            ->selectRaw('channel, count(*) as total, sum(case when delivery_status = ? then 1 else 0 end) as delivered', ['sent'])
            ->get();

        foreach ($channelStats as $stat) {
            $aggregated['by_channel'][$stat->channel] = [
                'total' => $stat->total,
                'delivered' => $stat->delivered,
                'rate' => $stat->total > 0 ? round(($stat->delivered / $stat->total) * 100, 2) : 0
            ];
        }

        return $aggregated;
    }

    /**
     * Get analytics with filters (controller-compatible method)
     */
    public function getAnalytics(array $filters = []): array
    {
        // Extract parameters from filters
        $userId = $filters['doctor_id'] ?? null;
        $startDate = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : null;
        $endDate = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : null;
        
        // Handle period-based date ranges
        if (isset($filters['period']) && !$startDate && !$endDate) {
            switch ($filters['period']) {
                case 'day':
                    $startDate = now()->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case 'week':
                    $startDate = now()->startOfWeek();
                    $endDate = now()->endOfWeek();
                    break;
                case 'month':
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
                    break;
                case 'quarter':
                    $startDate = now()->startOfQuarter();
                    $endDate = now()->endOfQuarter();
                    break;
                case 'year':
                    $startDate = now()->startOfYear();
                    $endDate = now()->endOfYear();
                    break;
                default:
                    $startDate = now()->subMonth();
                    $endDate = now();
            }
        }
        
        // Call the existing getReminderAnalytics method
        $analytics = $this->getReminderAnalytics($userId, $startDate, $endDate);
        
        // Add channel filtering if specified
        if (isset($filters['channel'])) {
            $channel = $filters['channel'];
            if (isset($analytics['by_channel'][$channel])) {
                $analytics['filtered_channel'] = $analytics['by_channel'][$channel];
            }
        }
        
        return $analytics;
    }

    /**
     * Clean up old reminder data
     */
    public function cleanupOldData(int $daysToKeep = 90): array
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);

            $deletedLogs = ReminderLog::where('created_at', '<', $cutoffDate)->delete();
            $deletedJobs = ScheduledReminderJob::where('created_at', '<', $cutoffDate)
                ->whereIn('status', ['completed', 'failed', 'cancelled'])
                ->delete();
            $deletedAnalytics = ReminderAnalytics::where('analytics_date', '<', $cutoffDate->format('Y-m-d'))->delete();

            Log::info("Reminder data cleanup completed", [
                'cutoff_date' => $cutoffDate,
                'deleted_logs' => $deletedLogs,
                'deleted_jobs' => $deletedJobs,
                'deleted_analytics' => $deletedAnalytics
            ]);

            return [
                'success' => true,
                'message' => 'Data cleanup completed',
                'deleted' => [
                    'logs' => $deletedLogs,
                    'jobs' => $deletedJobs,
                    'analytics' => $deletedAnalytics
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to cleanup reminder data", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ];
        }
    }    /**
     * Get user's reminder settings or create defaults - made public for observer access
     */
    public function getUserReminderSettings(int $userId): array
    {
        $reminderSetting = ReminderSetting::where('user_id', $userId)->first();
          if (!$reminderSetting) {
            $defaults = ReminderSetting::getDefaults('patient');
            $defaults['user_id'] = $userId;
            $reminderSetting = ReminderSetting::create($defaults);
        }

        return $reminderSetting->toArray();
    }

    /**
     * Apply custom settings to user's reminder settings
     */
    private function applyCustomSettings(array $userSettings, array $customSettings): array
    {
        // Merge custom settings with user settings
        $merged = array_merge($userSettings, $customSettings);
        
        // Validate the merged settings
        return $this->validateReminderSettings($merged);
    }

    /**
     * Cancel existing reminders for an appointment
     */
    private function cancelExistingReminders(int $appointmentId, string $reason): void
    {
        $existingJobs = ScheduledReminderJob::where('appointment_id', $appointmentId)
            ->where('status', 'pending')
            ->count();

        if ($existingJobs > 0) {
            CancelScheduledReminders::dispatch($appointmentId, $reason);
        }
    }

    /**
     * Validate reminder settings
     */    private function validateReminderSettings(array $settings): array
    {
        $validated = [];

        // Validate channel settings
        if (isset($settings['email_enabled'])) {
            $validated['email_enabled'] = (bool) $settings['email_enabled'];
        }
        if (isset($settings['push_enabled'])) {
            $validated['push_enabled'] = (bool) $settings['push_enabled'];
        }
        if (isset($settings['sms_enabled'])) {
            $validated['sms_enabled'] = (bool) $settings['sms_enabled'];
        }

        // Validate reminder timing settings
        if (isset($settings['first_reminder_hours'])) {
            $validated['first_reminder_hours'] = max(1, min(168, (int) $settings['first_reminder_hours']));
        }
        if (isset($settings['second_reminder_hours'])) {
            $validated['second_reminder_hours'] = max(1, min(24, (int) $settings['second_reminder_hours']));
        }
        if (isset($settings['reminder_24h_enabled'])) {
            $validated['reminder_24h_enabled'] = (bool) $settings['reminder_24h_enabled'];
        }
        if (isset($settings['reminder_2h_enabled'])) {
            $validated['reminder_2h_enabled'] = (bool) $settings['reminder_2h_enabled'];
        }

        // Validate other settings
        if (isset($settings['timezone'])) {
            $validated['timezone'] = $settings['timezone'];
        }
        if (isset($settings['is_active'])) {
            $validated['is_active'] = (bool) $settings['is_active'];
        }
        if (isset($settings['preferred_channels'])) {
            $validChannels = ['email', 'push', 'sms'];
            $preferredChannels = is_array($settings['preferred_channels']) 
                ? $settings['preferred_channels'] 
                : json_decode($settings['preferred_channels'], true) ?? [];
            $validated['preferred_channels'] = array_intersect($preferredChannels, $validChannels);
        }
        if (isset($settings['custom_settings'])) {
            $validated['custom_settings'] = is_array($settings['custom_settings']) 
                ? $settings['custom_settings'] 
                : json_decode($settings['custom_settings'], true) ?? [];
        }

        return $validated;
    }

    /**
     * Calculate delivery rate for an appointment
     */
    private function calculateDeliveryRate(int $appointmentId): float
    {
        $total = ReminderLog::where('appointment_id', $appointmentId)->count();
        if ($total === 0) {
            return 0;
        }

        $delivered = ReminderLog::where('appointment_id', $appointmentId)
            ->where('status', 'sent')
            ->count();

        return round(($delivered / $total) * 100, 2);
    }    /**
     * Get reminder settings for a user
     */
    public function getReminderSettings(int $userId): ?ReminderSetting
    {
        return ReminderSetting::where('user_id', $userId)->first() ?: 
               ReminderSetting::create(array_merge(
                   ReminderSetting::getDefaults('patient'),
                   ['user_id' => $userId]
               ));
    }

    /**
     * Update reminder settings for a user
     */
    public function updateReminderSettings(int $userId, array $settings): ReminderSetting
    {
        try {
            DB::beginTransaction();

            $defaults = ReminderSetting::getDefaults('patient');
            $defaults['user_id'] = $userId;
            
            $reminderSetting = ReminderSetting::firstOrCreate(
                ['user_id' => $userId],
                $defaults
            );

            // Validate and apply settings
            $validatedSettings = $this->validateReminderSettings($settings);
            $reminderSetting->update($validatedSettings);

            DB::commit();

            Log::info("Reminder settings updated", [
                'user_id' => $userId,
                'settings' => $validatedSettings
            ]);

            return $reminderSetting->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to update reminder settings", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get all reminders for a specific appointment
     */
    public function getAppointmentReminders(int $appointmentId): array
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) {
            throw new \InvalidArgumentException('Appointment not found');
        }

        // Get scheduled reminders
        $scheduledReminders = ScheduledReminderJob::where('appointment_id', $appointmentId)
            ->orderBy('scheduled_for', 'asc')
            ->get();

        // Get reminder logs (sent reminders)
        $reminderLogs = ReminderLog::where('appointment_id', $appointmentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'appointment_id' => $appointmentId,
            'appointment_date' => $appointment->appointment_datetime_start,
            'scheduled_reminders' => $scheduledReminders->map(function ($reminder) {
                return [
                    'id' => $reminder->id,
                    'type' => $reminder->reminder_type,
                    'channel' => $reminder->channel,
                    'scheduled_for' => $reminder->scheduled_for,
                    'status' => $reminder->status,
                    'created_at' => $reminder->created_at,
                    'executed_at' => $reminder->executed_at,
                    'failure_reason' => $reminder->failure_reason
                ];
            }),
            'sent_reminders' => $reminderLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->reminder_type,
                    'channel' => $log->channel,
                    'status' => $log->delivery_status,
                    'sent_at' => $log->sent_at,
                    'delivered_at' => $log->delivered_at,
                    'error_message' => $log->error_message,
                    'message_content' => $log->message_content
                ];
            })
        ];
    }

    /**
     * Schedule a custom reminder for an appointment
     */
    public function scheduleCustomReminder(Appointment $appointment, array $reminderData): array
    {
        try {
            DB::beginTransaction();

            // Validate timing
            $scheduledFor = Carbon::parse($reminderData['scheduled_for']);
            if ($scheduledFor <= now()) {
                throw new \InvalidArgumentException('Reminder must be scheduled for a future time');
            }

            if ($scheduledFor >= $appointment->appointment_datetime_start) {
                throw new \InvalidArgumentException('Reminder must be scheduled before the appointment');
            }

            // Get channels array (support both single channel and multiple channels)
            $channels = isset($reminderData['channels']) ? $reminderData['channels'] : [$reminderData['channel']];
            $scheduledReminders = [];

            // Create scheduled reminder job for each channel
            foreach ($channels as $channel) {
                // Generate unique job ID
                $jobId = 'reminder_' . $appointment->id . '_' . $channel . '_' . time() . '_' . uniqid();
                
                $scheduledReminder = ScheduledReminderJob::create([
                    'appointment_id' => $appointment->id,
                    'job_id' => $jobId,
                    'reminder_type' => $reminderData['type'] ?? $reminderData['reminder_type'] ?? 'custom',
                    'channel' => $channel,
                    'scheduled_for' => $scheduledFor,
                    'status' => 'pending',
                    'job_payload' => [
                        'custom_message' => $reminderData['message'] ?? null,
                        'priority' => $reminderData['priority'] ?? 'normal',
                        'scheduled_by_user_id' => $reminderData['scheduled_by_user_id'] ?? null
                    ]
                ]);

                // Dispatch the reminder job
                SendAppointmentReminder::dispatch(
                    $appointment->id,
                    $appointment->patient_user_id,
                    $channel,
                    $reminderData['type'] ?? $reminderData['reminder_type'] ?? 'custom',
                    $reminderData
                )->delay($scheduledFor);

                $scheduledReminders[] = $scheduledReminder;
            }

            DB::commit();

            Log::info("Custom reminder scheduled", [
                'appointment_id' => $appointment->id,
                'scheduled_for' => $scheduledFor,
                'channels' => $channels,
                'reminder_count' => count($scheduledReminders)
            ]);

            return [
                'success' => true,
                'message' => 'Custom reminder scheduled successfully',
                'reminder_id' => $scheduledReminders[0]->id, // Return first reminder ID for compatibility
                'reminder_ids' => array_map(fn($r) => $r->id, $scheduledReminders),
                'scheduled_for' => $scheduledFor,
                'channels_scheduled' => $channels,
                'total_reminders' => count($scheduledReminders)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to schedule custom reminder", [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to schedule custom reminder: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel a specific reminder
     */
    public function cancelSpecificReminder(int $reminderId): array
    {
        try {
            $reminder = ScheduledReminderJob::find($reminderId);
            if (!$reminder) {
                throw new \InvalidArgumentException('Reminder not found');
            }

            if ($reminder->status !== 'pending') {
                throw new \InvalidArgumentException('Can only cancel pending reminders');
            }

            $reminder->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'failure_reason' => 'Manual cancellation'
            ]);

            Log::info("Specific reminder cancelled", [
                'reminder_id' => $reminderId,
                'appointment_id' => $reminder->appointment_id
            ]);

            return [
                'success' => true,
                'message' => 'Reminder cancelled successfully',
                'reminder_id' => $reminderId
            ];

        } catch (\Exception $e) {
            Log::error("Failed to cancel specific reminder", [
                'reminder_id' => $reminderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel reminder: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reschedule a specific reminder
     */
    public function rescheduleReminder(int $reminderId, string $newDateTime, ?int $userId = null, ?string $reason = null): array
    {
        try {
            DB::beginTransaction();

            $reminder = ScheduledReminderJob::find($reminderId);
            if (!$reminder) {
                throw new \InvalidArgumentException('Reminder not found');
            }

            if ($reminder->status !== 'pending') {
                throw new \InvalidArgumentException('Can only reschedule pending reminders');
            }

            $newScheduledFor = Carbon::parse($newDateTime);
            if ($newScheduledFor <= now()) {
                throw new \InvalidArgumentException('New time must be in the future');
            }

            $appointment = Appointment::find($reminder->appointment_id);
            if ($newScheduledFor >= $appointment->appointment_datetime_start) {
                throw new \InvalidArgumentException('Reminder must be scheduled before the appointment');
            }

            $oldScheduledFor = $reminder->scheduled_for;
            
            // Update the reminder with new schedule and metadata
            $updateData = ['scheduled_for' => $newScheduledFor];
            if ($reason) {
                // Safely handle job_payload which might be string, array, or null
                $payload = $reminder->job_payload;
                if (is_string($payload)) {
                    $payload = json_decode($payload, true) ?? [];
                } elseif (!is_array($payload)) {
                    $payload = [];
                }
                
                $payload['reschedule_reason'] = $reason;
                $payload['rescheduled_by_user_id'] = $userId;
                $payload['rescheduled_at'] = now()->toISOString();
                $updateData['job_payload'] = $payload;
            }
            
            $reminder->update($updateData);

            // Re-dispatch the job with new timing
            $appointment = Appointment::find($reminder->appointment_id);
            
            // Safely handle job_payload which might be string, array, or null
            $jobPayload = $reminder->job_payload;
            if (is_string($jobPayload)) {
                $jobPayload = json_decode($jobPayload, true) ?? [];
            } elseif (!is_array($jobPayload)) {
                $jobPayload = [];
            }
            
            SendAppointmentReminder::dispatch(
                $reminder->appointment_id,
                $appointment->patient_user_id,  // Get user_id from appointment since ScheduledReminderJob doesn't have user_id
                $reminder->channel,
                $reminder->reminder_type,
                [
                    'custom_message' => $jobPayload['custom_message'] ?? null,
                    'priority' => $jobPayload['priority'] ?? 'normal'
                ]
            )->delay($newScheduledFor);

            DB::commit();

            Log::info("Reminder rescheduled", [
                'reminder_id' => $reminderId,
                'old_time' => $oldScheduledFor,
                'new_time' => $newScheduledFor,
                'rescheduled_by_user_id' => $userId,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => 'Reminder rescheduled successfully',
                'reminder_id' => $reminderId,
                'old_time' => $oldScheduledFor,
                'new_scheduled_time' => $newScheduledFor
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to reschedule reminder", [
                'reminder_id' => $reminderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reschedule reminder: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get delivery status for reminders of an appointment
     */
    public function getReminderDeliveryStatus(int $appointmentId): array
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) {
            throw new \InvalidArgumentException('Appointment not found');
        }

        // Get all reminder logs for this appointment
        $reminderLogs = ReminderLog::where('appointment_id', $appointmentId)
            ->orderBy('sent_at', 'desc')
            ->get();

        // Get scheduled reminders
        $scheduledReminders = ScheduledReminderJob::where('appointment_id', $appointmentId)
            ->orderBy('scheduled_for', 'asc')
            ->get();        $lastReminderSent = $reminderLogs->first();
        $nextScheduled = $scheduledReminders->where('status', 'pending')->first();
        
        $deliveryStatus = [
            'appointment_id' => $appointmentId,
            'total_sent' => $reminderLogs->count(),
            'failed_count' => $reminderLogs->where('delivery_status', 'failed')->count(),
            'total_scheduled' => $scheduledReminders->count(),
            'summary' => [
                'delivered' => $reminderLogs->where('delivery_status', 'sent')->count(),
                'failed' => $reminderLogs->where('delivery_status', 'failed')->count(),
                'pending' => $scheduledReminders->where('status', 'pending')->count(),
                'cancelled' => $scheduledReminders->where('status', 'cancelled')->count()
            ],
            'breakdown' => [],
            'last_sent' => $lastReminderSent ? $lastReminderSent->sent_at : null,
            'next_scheduled' => $nextScheduled ? $nextScheduled->scheduled_for : null,
            'recent_deliveries' => $reminderLogs->take(10)->map(function ($log) {
                return [
                    'id' => $log->id,
                    'channel' => $log->channel,
                    'status' => $log->delivery_status,
                    'sent_at' => $log->sent_at,
                    'delivered_at' => $log->delivered_at,
                    'error_message' => $log->error_message
                ];
            })
        ];        // Group by channel
        foreach (['email', 'sms', 'push', 'in_app'] as $channel) {
            $channelLogs = $reminderLogs->where('channel', $channel);
            $deliveryStatus['breakdown'][$channel] = [
                'total' => $channelLogs->count(),
                'delivered' => $channelLogs->where('delivery_status', 'sent')->count(),
                'failed' => $channelLogs->where('delivery_status', 'failed')->count(),
                'rate' => $channelLogs->count() > 0 ? 
                    round(($channelLogs->where('delivery_status', 'sent')->count() / $channelLogs->count()) * 100, 2) : 0
            ];
        }

        return $deliveryStatus;
    }

    /**
     * Update reminder preferences for a specific appointment
     */
    public function updateAppointmentReminderPreferences(int $appointmentId, array $preferences): array
    {
        try {
            DB::beginTransaction();

            $appointment = Appointment::find($appointmentId);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            // Get or create appointment-specific preferences
            $appointmentMeta = $appointment->meta ?? [];
            $appointmentMeta['reminder_preferences'] = array_merge(
                $appointmentMeta['reminder_preferences'] ?? [],
                $preferences
            );

            $appointment->update(['meta' => $appointmentMeta]);

            // If there are active reminders, reschedule them with new preferences
            if (isset($preferences['channels']) || isset($preferences['timing'])) {
                $this->rescheduleAppointmentReminders($appointmentId, $preferences);
            }

            DB::commit();

            Log::info("Appointment reminder preferences updated", [
                'appointment_id' => $appointmentId,
                'preferences' => $preferences
            ]);

            return [
                'success' => true,
                'message' => 'Appointment reminder preferences updated successfully',
                'appointment_id' => $appointmentId,
                'preferences' => $appointmentMeta['reminder_preferences']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to update appointment reminder preferences", [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update preferences: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Acknowledge a received reminder
     */
    public function acknowledgeReminder(int $reminderLogId, int $userId): array
    {
        try {
            $reminderLog = ReminderLog::find($reminderLogId);
            if (!$reminderLog) {
                throw new \InvalidArgumentException('Reminder log not found');
            }

            if ($reminderLog->user_id !== $userId) {
                throw new \InvalidArgumentException('Unauthorized to acknowledge this reminder');
            }

            $reminderLog->update([
                'acknowledged_at' => now(),
                'acknowledgment_method' => 'manual'
            ]);

            Log::info("Reminder acknowledged", [
                'reminder_log_id' => $reminderLogId,
                'user_id' => $userId
            ]);

            return [
                'success' => true,
                'message' => 'Reminder acknowledged successfully',
                'reminder_id' => $reminderLogId,
                'acknowledged_at' => $reminderLog->acknowledged_at
            ];

        } catch (\Exception $e) {
            Log::error("Failed to acknowledge reminder", [
                'reminder_log_id' => $reminderLogId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to acknowledge reminder: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Opt out user from reminders
     */
    public function optOutReminders(int $userId, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $reminderSetting = $this->getReminderSettings($userId);
            
            // Update settings to disable reminders
            $reminderSetting->update([
                'is_active' => false,
                'email_enabled' => false,
                'sms_enabled' => false,
                'push_enabled' => false,
                'opted_out_at' => now(),
                'opt_out_reason' => $options['reason'] ?? 'user_request'
            ]);

            // Cancel all pending reminders for this user
            $pendingReminders = ScheduledReminderJob::where('user_id', $userId)
                ->where('status', 'pending')
                ->get();

            foreach ($pendingReminders as $reminder) {
                $reminder->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'failure_reason' => 'User opted out'
                ]);
            }

            DB::commit();

            Log::info("User opted out of reminders", [
                'user_id' => $userId,
                'cancelled_reminders' => $pendingReminders->count(),
                'reason' => $options['reason'] ?? 'user_request'
            ]);

            return [
                'success' => true,
                'message' => 'Successfully opted out of reminders',
                'user_id' => $userId,
                'cancelled_reminders' => $pendingReminders->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to opt out user from reminders", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to opt out: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reschedule appointment reminders with new preferences
     */
    private function rescheduleAppointmentReminders(int $appointmentId, array $preferences): void
    {
        // Cancel existing pending reminders
        $this->cancelExistingReminders($appointmentId, 'preferences_updated');

        // Reschedule with new preferences
        $appointment = Appointment::find($appointmentId);
        if ($appointment && $appointment->appointment_datetime_start > now()) {
            $this->scheduleReminders($appointment, $preferences);
        }
    }

    /**
     * Calculate how many reminders will be scheduled based on settings
     */
    private function calculateScheduledCount(array $reminderSettings): int
    {
        $count = 0;
        
        // Count enabled reminder times
        if ($reminderSettings['reminder_24h_enabled'] ?? true) {
            $count++;
        }
        if ($reminderSettings['reminder_2h_enabled'] ?? true) {
            $count++;
        }
        
        // Count enabled channels
        $enabledChannels = 0;
        if ($reminderSettings['email_enabled'] ?? false) $enabledChannels++;
        if ($reminderSettings['sms_enabled'] ?? false) $enabledChannels++;
        if ($reminderSettings['push_enabled'] ?? false) $enabledChannels++;
        
        return $count * max(1, $enabledChannels);
    }
    
    /**
     * Get default reminder times based on settings
     */
    private function getDefaultReminderTimes(array $reminderSettings): array
    {
        $times = [];
        
        if ($reminderSettings['reminder_24h_enabled'] ?? true) {
            $times[] = ($reminderSettings['first_reminder_hours'] ?? 24) * 60; // Convert to minutes
        }
        if ($reminderSettings['reminder_2h_enabled'] ?? true) {
            $times[] = ($reminderSettings['second_reminder_hours'] ?? 2) * 60; // Convert to minutes
        }
        
        return $times;
    }
    
    /**
     * Get enabled channels from settings
     */
    private function getEnabledChannels(array $reminderSettings): array
    {
        $channels = [];
        
        if ($reminderSettings['email_enabled'] ?? false) {
            $channels[] = 'email';
        }
        if ($reminderSettings['sms_enabled'] ?? false) {
            $channels[] = 'sms';
        }
        if ($reminderSettings['push_enabled'] ?? false) {
            $channels[] = 'push';
        }
        
        return $channels;
    }

    /**
     * Cancel reminders for an appointment (alias for cancelReminders)
     * This method is used by the AppointmentObserver for better semantic clarity
     */
    public function cancelAppointmentReminders(int $appointmentId, string $reason = 'manual_cancellation'): array
    {
        return $this->cancelReminders($appointmentId, $reason);
    }

    /**
     * Log a reminder activity to the database
     */
    public function logReminder(int $appointmentId, string $channel, string $reminderType, string $status, array $data = []): ?ReminderLog
    {
        try {
            $appointment = Appointment::find($appointmentId);
            if (!$appointment) {
                Log::warning("Cannot log reminder for non-existent appointment", [
                    'appointment_id' => $appointmentId,
                    'channel' => $channel,
                    'reminder_type' => $reminderType,
                    'status' => $status
                ]);
                return null;
            }

            // Create the reminder log entry
            $reminderLog = ReminderLog::create([
                'appointment_id' => $appointmentId,
                'user_id' => $appointment->patient_user_id,
                'reminder_type' => $reminderType,
                'channel' => $channel,
                'trigger_type' => 'manual', // Default for logged reminders
                'delivery_status' => $status,
                'sent_at' => $data['sent_at'] ?? now(),
                'message_content' => $data['message'] ?? null,
                'metadata' => array_merge([
                    'logged_at' => now()->toISOString(),
                    'log_source' => 'reminder_service'
                ], $data),
                'job_id' => $data['job_id'] ?? 'manual_' . time() . '_' . uniqid()
            ]);

            Log::info("Reminder activity logged", [
                'reminder_log_id' => $reminderLog->id,
                'appointment_id' => $appointmentId,
                'channel' => $channel,
                'reminder_type' => $reminderType,
                'status' => $status
            ]);

            return $reminderLog;

        } catch (\Exception $e) {
            Log::error("Failed to log reminder activity", [
                'appointment_id' => $appointmentId,
                'channel' => $channel,
                'reminder_type' => $reminderType,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }
}
