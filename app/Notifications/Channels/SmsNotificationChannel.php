<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsNotificationChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): ?array
    {
        if (!method_exists($notification, 'toSms')) {
            return null;
        }

        $data = $notification->toSms($notifiable);
        
        if (!$data) {
            return null;
        }

        return $this->sendSms($notifiable, $data);
    }

    /**
     * Send SMS via configured provider
     */
    protected function sendSms(object $notifiable, array $data): array
    {
        try {
            $phoneNumber = $this->getPhoneNumber($notifiable);
            
            if (!$phoneNumber) {
                throw new Exception('No phone number found for user');
            }

            $provider = config('services.sms.provider', 'twilio');
            
            return match ($provider) {
                'twilio' => $this->sendViaTwilio($phoneNumber, $data),
                'nexmo' => $this->sendViaNexmo($phoneNumber, $data),
                'textlocal' => $this->sendViaTextLocal($phoneNumber, $data),
                default => throw new Exception('Unsupported SMS provider: ' . $provider)
            };

        } catch (Exception $e) {
            Log::error('Failed to send SMS notification', [
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
     * Send SMS via Twilio
     */
    protected function sendViaTwilio(string $phoneNumber, array $data): array
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $fromNumber = config('services.twilio.from');

        if (!$accountSid || !$authToken || !$fromNumber) {
            throw new Exception('Twilio configuration missing');
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $fromNumber,
                'To' => $phoneNumber,
                'Body' => $data['message']
            ]);

        if ($response->successful()) {
            Log::info('SMS sent via Twilio', [
                'phone' => $phoneNumber,
                'sid' => $response->json()['sid'] ?? null
            ]);

            return [
                'success' => true,
                'provider' => 'twilio',
                'message_id' => $response->json()['sid'] ?? null,
                'response' => $response->json()
            ];
        } else {
            throw new Exception('Twilio API error: ' . $response->body());
        }
    }

    /**
     * Send SMS via Nexmo/Vonage
     */
    protected function sendViaNexmo(string $phoneNumber, array $data): array
    {
        $apiKey = config('services.nexmo.key');
        $apiSecret = config('services.nexmo.secret');
        $from = config('services.nexmo.from');

        if (!$apiKey || !$apiSecret || !$from) {
            throw new Exception('Nexmo configuration missing');
        }

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $from,
            'to' => $phoneNumber,
            'text' => $data['message']
        ]);

        if ($response->successful()) {
            $result = $response->json();
            
            if ($result['messages'][0]['status'] === '0') {
                Log::info('SMS sent via Nexmo', [
                    'phone' => $phoneNumber,
                    'message_id' => $result['messages'][0]['message-id']
                ]);

                return [
                    'success' => true,
                    'provider' => 'nexmo',
                    'message_id' => $result['messages'][0]['message-id'],
                    'response' => $result
                ];
            } else {
                throw new Exception('Nexmo error: ' . $result['messages'][0]['error-text']);
            }
        } else {
            throw new Exception('Nexmo API error: ' . $response->body());
        }
    }

    /**
     * Send SMS via TextLocal
     */
    protected function sendViaTextLocal(string $phoneNumber, array $data): array
    {
        $apiKey = config('services.textlocal.api_key');
        $sender = config('services.textlocal.sender');

        if (!$apiKey || !$sender) {
            throw new Exception('TextLocal configuration missing');
        }

        $response = Http::asForm()->post('https://api.textlocal.in/send/', [
            'apikey' => $apiKey,
            'sender' => $sender,
            'numbers' => $phoneNumber,
            'message' => $data['message']
        ]);

        if ($response->successful()) {
            $result = $response->json();
            
            if ($result['status'] === 'success') {
                Log::info('SMS sent via TextLocal', [
                    'phone' => $phoneNumber,
                    'message_id' => $result['messages'][0]['id'] ?? null
                ]);

                return [
                    'success' => true,
                    'provider' => 'textlocal',
                    'message_id' => $result['messages'][0]['id'] ?? null,
                    'response' => $result
                ];
            } else {
                throw new Exception('TextLocal error: ' . ($result['errors'][0]['message'] ?? 'Unknown error'));
            }
        } else {
            throw new Exception('TextLocal API error: ' . $response->body());
        }
    }

    /**
     * Get phone number for the user
     */
    protected function getPhoneNumber(object $notifiable): ?string
    {
        // Try various common phone number fields
        $phoneFields = ['phone', 'phone_number', 'mobile', 'mobile_number', 'contact_number'];
        
        foreach ($phoneFields as $field) {
            if (isset($notifiable->$field) && !empty($notifiable->$field)) {
                return $this->formatPhoneNumber($notifiable->$field);
            }
        }

        return null;
    }

    /**
     * Format phone number for international SMS
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present (assuming US/Canada +1)
        if (strlen($phone) === 10) {
            $phone = '1' . $phone;
        }
        
        // Add + prefix if not present
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }
}
