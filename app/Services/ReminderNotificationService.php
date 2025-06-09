<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Carbon\Carbon;

class ReminderNotificationService
{
    /**
     * Send a reminder through the specified channel
     */
    public function sendReminder(
        Appointment $appointment, 
        User $user, 
        string $channel, 
        string $reminderType, 
        array $data = []
    ): array {
        try {
            $result = match ($channel) {
                'email' => $this->sendEmailReminder($appointment, $user, $reminderType, $data),
                'sms' => $this->sendSmsReminder($appointment, $user, $reminderType, $data),
                'push' => $this->sendPushReminder($appointment, $user, $reminderType, $data),
                'in_app' => $this->sendInAppReminder($appointment, $user, $reminderType, $data),
                default => throw new \InvalidArgumentException("Unsupported channel: {$channel}")
            };

            // Log successful send
            Log::info("Reminder sent successfully", [
                'appointment_id' => $appointment->id,
                'user_id' => $user->id,
                'channel' => $channel,
                'reminder_type' => $reminderType,
                'message_id' => $result['message_id'] ?? null
            ]);

            return [
                'success' => true,
                'channel' => $channel,
                'message_id' => $result['message_id'] ?? null,
                'sent_at' => now(),
                'metadata' => $result['metadata'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error("Failed to send reminder", [
                'appointment_id' => $appointment->id,
                'user_id' => $user->id,
                'channel' => $channel,
                'reminder_type' => $reminderType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'channel' => $channel,
                'error' => $e->getMessage(),
                'failed_at' => now()
            ];
        }
    }

    /**
     * Send email reminder
     */
    private function sendEmailReminder(Appointment $appointment, User $user, string $reminderType, array $data): array
    {
        $subject = $this->getEmailSubject($appointment, $reminderType);
        $template = $this->getEmailTemplate($reminderType);
        
        $templateData = [
            'user' => $user,
            'appointment' => $appointment,
            'reminder_type' => $reminderType,
            'reminderType' => $reminderType, // Add camelCase version for Blade template
            'custom_message' => $data['custom_message'] ?? null,
            'appointment_date' => $appointment->appointment_datetime_start,
            'formatted_date' => $appointment->appointment_datetime_start->format('l, F j, Y'),
            'formatted_time' => $appointment->appointment_datetime_start->format('g:i A'),
            'time_until' => $this->getTimeUntilAppointment($appointment),
            'clinic_info' => $this->getClinicInfo(),
            'cancellation_link' => $this->generateCancellationLink($appointment),
            'reschedule_link' => $this->generateRescheduleLink($appointment)
        ];

        // Send the email
        Mail::send($template, $templateData, function ($message) use ($user, $subject) {
            $message->to($user->email, $user->name)
                    ->subject($subject);
            
            // Add any attachments if specified
            if (config('reminders.email.include_ical')) {
                $message->attach($this->generateICalAttachment($appointment));
            }
        });

        return [
            'message_id' => uniqid('email_' . time() . '_'),
            'metadata' => [
                'template' => $template,
                'subject' => $subject,
                'recipient' => $user->email
            ]
        ];
    }

    /**
     * Send SMS reminder
     */
    private function sendSmsReminder(Appointment $appointment, User $user, string $reminderType, array $data): array
    {
        // Check if SMS service is configured
        if (!config('reminders.sms.enabled', false)) {
            throw new \Exception('SMS service is not enabled');
        }

        if (empty($user->phone)) {
            throw new \Exception('User does not have a phone number');
        }

        $message = $this->getSmsMessage($appointment, $user, $reminderType, $data);
        
        // Using a hypothetical SMS service - replace with your actual SMS provider
        // Examples: Twilio, Nexmo, AWS SNS, etc.
        $smsService = app('sms'); // Assume SMS service is bound in service container
        
        $response = $smsService->send($user->phone, $message);

        return [
            'message_id' => $response['message_id'] ?? uniqid('sms_'),
            'metadata' => [
                'phone' => $user->phone,
                'message_length' => strlen($message),
                'provider_response' => $response
            ]
        ];
    }

    /**
     * Send push notification reminder
     */
    private function sendPushReminder(Appointment $appointment, User $user, string $reminderType, array $data): array
    {
        // Check if push notifications are configured
        if (!config('reminders.push.enabled', false)) {
            throw new \Exception('Push notifications are not enabled');
        }

        $title = $this->getPushTitle($reminderType);
        $body = $this->getPushMessage($appointment, $user, $reminderType, $data);
        
        // Using Firebase Cloud Messaging or similar service
        $pushService = app('push'); // Assume push service is bound in service container
        
        $payload = [
            'title' => $title,
            'body' => $body,
            'data' => [
                'appointment_id' => $appointment->id,
                'reminder_type' => $reminderType,
                'appointment_date' => $appointment->appointment_datetime_start->toISOString(),
                'action' => 'view_appointment'
            ],
            'click_action' => route('appointments.show', $appointment->id)
        ];

        $response = $pushService->sendToUser($user, $payload);

        return [
            'message_id' => $response['message_id'] ?? uniqid('push_'),
            'metadata' => [
                'title' => $title,
                'body' => $body,
                'provider_response' => $response
            ]
        ];
    }

    /**
     * Send in-app notification reminder
     */
    private function sendInAppReminder(Appointment $appointment, User $user, string $reminderType, array $data): array
    {
        $title = $this->getInAppTitle($reminderType);
        $message = $this->getInAppMessage($appointment, $user, $reminderType, $data);
        
        // Create database notification
        $notification = $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => 'App\\Notifications\\AppointmentReminder',
            'data' => [
                'title' => $title,
                'message' => $message,
                'appointment_id' => $appointment->id,
                'reminder_type' => $reminderType,
                'appointment_date' => $appointment->appointment_datetime_start->toISOString(),
                'action_url' => route('appointments.show', $appointment->id),
                'priority' => $data['priority'] ?? 'normal'
            ],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Optionally broadcast to real-time channels (WebSocket, Pusher, etc.)
        if (config('reminders.in_app.realtime_enabled', false)) {
            broadcast(new \App\Events\NewNotification($user, $notification));
        }

        return [
            'message_id' => $notification->id,
            'metadata' => [
                'title' => $title,
                'message' => $message,
                'realtime_sent' => config('reminders.in_app.realtime_enabled', false)
            ]
        ];
    }

    /**
     * Get email subject based on reminder type
     */
    private function getEmailSubject(Appointment $appointment, string $reminderType): string
    {
        $subjects = [
            '24h' => 'Reminder: Your appointment is tomorrow',
            '2h' => 'Reminder: Your appointment is in 2 hours',
            '1h' => 'Reminder: Your appointment is in 1 hour',
            'manual' => 'Important: Appointment reminder',
            'custom' => 'Appointment reminder'
        ];

        return $subjects[$reminderType] ?? 'Appointment reminder';
    }

    /**
     * Get email template based on reminder type
     */
    private function getEmailTemplate(string $reminderType): string
    {
        // Use the generic appointment reminder template for all reminder types
        return 'emails.reminders.appointment-reminder';
    }

    /**
     * Get SMS message
     */
    private function getSmsMessage(Appointment $appointment, User $user, string $reminderType, array $data): string
    {
        if (!empty($data['custom_message'])) {
            return $data['custom_message'];
        }

        $timeUntil = $this->getTimeUntilAppointment($appointment);
        $date = $appointment->appointment_datetime_start->format('M j, Y');
        $time = $appointment->appointment_datetime_start->format('g:i A');

        $messages = [
            '24h' => "Hi {$user->name}! 👋 ASIO Reminder: You have an appointment tomorrow ({$date}) at {$time}. Reply STOP to opt out.",
            '2h' => "Hi {$user->name}! ⏰ ASIO: Your appointment is in 2 hours ({$time}). See you soon! Reply STOP to opt out.",
            '1h' => "Hi {$user->name}! 🏥 ASIO: Your appointment is in 1 hour ({$time}). Please arrive 10 minutes early. Reply STOP to opt out.",
            'manual' => "Hi {$user->name}! 📅 ASIO appointment reminder: {$date} at {$time}. Reply STOP to opt out."
        ];

        return $messages[$reminderType] ?? "Appointment reminder: {$date} at {$time}. Reply STOP to opt out.";
    }

    /**
     * Get push notification title
     */
    private function getPushTitle(string $reminderType): string
    {
        $titles = [
            '24h' => 'Appointment Tomorrow',
            '2h' => 'Appointment in 2 Hours',
            '1h' => 'Appointment in 1 Hour',
            'manual' => 'Appointment Reminder'
        ];

        return $titles[$reminderType] ?? 'Appointment Reminder';
    }

    /**
     * Get push notification message
     */
    private function getPushMessage(Appointment $appointment, User $user, string $reminderType, array $data): string
    {
        if (!empty($data['custom_message'])) {
            return $data['custom_message'];
        }

        $date = $appointment->appointment_datetime_start->format('M j');
        $time = $appointment->appointment_datetime_start->format('g:i A');

        $messages = [
            '24h' => "Your appointment is tomorrow at {$time}",
            '2h' => "Your appointment is at {$time} (in 2 hours)",
            '1h' => "Your appointment is at {$time} (in 1 hour)",
            'manual' => "Appointment: {$date} at {$time}"
        ];

        return $messages[$reminderType] ?? "Appointment: {$date} at {$time}";
    }

    /**
     * Get in-app notification title
     */
    private function getInAppTitle(string $reminderType): string
    {
        return $this->getPushTitle($reminderType);
    }

    /**
     * Get in-app notification message
     */
    private function getInAppMessage(Appointment $appointment, User $user, string $reminderType, array $data): string
    {
        return $this->getPushMessage($appointment, $user, $reminderType, $data);
    }

    /**
     * Get time until appointment in human readable format
     */
    private function getTimeUntilAppointment(Appointment $appointment): string
    {
        return $appointment->appointment_datetime_start->diffForHumans();
    }

    /**
     * Get clinic information for emails
     */
    private function getClinicInfo(): array
    {
        return [
            'name' => 'ASIO Healthcare Platform',
            'address' => config('clinic.address', ''),
            'phone' => config('clinic.phone', '(555) 123-4567'),
            'email' => config('clinic.email', 'support@asio.com'),
            'website' => config('app.url', 'https://asio.healthcare')
        ];
    }

    /**
     * Generate cancellation link
     */
    private function generateCancellationLink(Appointment $appointment): string
    {
        try {
            return route('appointments.cancel', [
                'appointment' => $appointment->id,
                'token' => encrypt($appointment->id . '|' . $appointment->patient_user_id)
            ]);
        } catch (\Exception $e) {
            // Fallback to a generic URL if route doesn't exist
            return config('app.url') . '/appointments/' . $appointment->id . '/cancel';
        }
    }

    /**
     * Generate reschedule link
     */
    private function generateRescheduleLink(Appointment $appointment): string
    {
        try {
            return route('appointments.reschedule', [
                'appointment' => $appointment->id,
                'token' => encrypt($appointment->id . '|' . $appointment->patient_user_id)
            ]);
        } catch (\Exception $e) {
            // Fallback to a generic URL if route doesn't exist
            return config('app.url') . '/appointments/' . $appointment->id . '/reschedule';
        }
    }

    /**
     * Generate iCal attachment for email
     */
    private function generateICalAttachment(Appointment $appointment): string
    {
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Your Clinic//Appointment Reminder//EN\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $appointment->id . "@" . config('app.url') . "\r\n";
        $ical .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $appointment->appointment_datetime_start->format('Ymd\THis\Z') . "\r\n";
        $ical .= "DTEND:" . $appointment->appointment_datetime_end->format('Ymd\THis\Z') . "\r\n";
        $ical .= "SUMMARY:Medical Appointment\r\n";
        $ical .= "DESCRIPTION:Your scheduled appointment\r\n";
        $ical .= "LOCATION:" . config('clinic.address', '') . "\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        $filename = storage_path('app/temp/appointment_' . $appointment->id . '.ics');
        file_put_contents($filename, $ical);

        return $filename;
    }

    /**
     * Validate reminder delivery result and update logs
     */
    public function updateReminderLog(ReminderLog $log, array $result): void
    {
        try {
            if ($result['success']) {
                $log->update([
                    'status' => 'sent',
                    'sent_at' => $result['sent_at'],
                    'message_id' => $result['message_id'],
                    'metadata' => array_merge($log->metadata ?? [], $result['metadata'] ?? [])
                ]);
            } else {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $result['error'],
                    'failed_at' => $result['failed_at'],
                    'metadata' => array_merge($log->metadata ?? [], ['failure_reason' => $result['error']])
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update reminder log", [
                'log_id' => $log->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test notification channels
     */
    public function testNotificationChannels(User $user): array
    {
        $results = [];
        $channels = ['email', 'sms', 'push', 'in_app'];

        foreach ($channels as $channel) {
            try {
                // Create a dummy appointment for testing
                $testAppointment = new Appointment([
                    'id' => 0,
                    'user_id' => $user->id,
                    'appointment_date' => now()->addDay(),
                    'status' => 'scheduled'
                ]);

                $result = $this->sendReminder(
                    $testAppointment, 
                    $user, 
                    $channel, 
                    'manual', 
                    ['custom_message' => 'This is a test notification from the reminder system.']
                );

                $results[$channel] = [
                    'success' => $result['success'],
                    'message' => $result['success'] ? 'Test notification sent successfully' : $result['error']
                ];

            } catch (\Exception $e) {
                $results[$channel] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
