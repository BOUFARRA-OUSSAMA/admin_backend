<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PushNotificationChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): ?array
    {
        if (!method_exists($notification, 'toPush')) {
            return null;
        }

        $data = $notification->toPush($notifiable);
        
        if (!$data) {
            return null;
        }

        return $this->sendPushNotification($notifiable, $data);
    }

    /**
     * Send push notification via Firebase Cloud Messaging (FCM)
     */
    protected function sendPushNotification(object $notifiable, array $data): array
    {
        try {
            // Get FCM token from the user
            $fcmToken = $this->getFcmToken($notifiable);
            
            if (!$fcmToken) {
                throw new Exception('No FCM token found for user');
            }

            $payload = [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $data['title'] ?? 'Appointment Reminder',
                    'body' => $data['body'] ?? 'You have an upcoming appointment',
                    'icon' => $data['icon'] ?? asset('favicon.ico'),
                    'click_action' => $data['click_action'] ?? url('/appointments'),
                    'sound' => 'default',
                ],
                'data' => [
                    'appointment_id' => $data['appointment_id'] ?? null,
                    'type' => $data['type'] ?? 'reminder',
                    'reminder_type' => $data['reminder_type'] ?? null,
                    'url' => $data['url'] ?? null,
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info('Push notification sent successfully', [
                    'user_id' => $notifiable->id,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'response' => $response->json(),
                    'message_id' => $response->json()['results'][0]['message_id'] ?? null
                ];
            } else {
                throw new Exception('FCM request failed: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $notifiable->id ?? null,
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get FCM token for the user
     */
    protected function getFcmToken(object $notifiable): ?string
    {
        // Check if user has fcm_token field
        if (isset($notifiable->fcm_token)) {
            return $notifiable->fcm_token;
        }

        // Check user_devices table if exists
        if (method_exists($notifiable, 'devices')) {
            $device = $notifiable->devices()
                ->where('platform', 'fcm')
                ->where('is_active', true)
                ->first();
            
            return $device?->token;
        }

        // Fallback: check for push_token or device_token
        return $notifiable->push_token ?? $notifiable->device_token ?? null;
    }
}
