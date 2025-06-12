    /**
     * Send immediate reminder for testing purposes
     */
    public function sendImmediateReminder(Appointment $appointment, array $channels, string $customMessage = null): array
    {
        try {
            $sentChannels = [];
            $failedChannels = [];
            
            foreach ($channels as $channel) {
                try {
                    // Dispatch immediate reminder job
                    $job = SendAppointmentReminder::dispatch(
                        $appointment->id,
                        $channel,
                        $customMessage ?? 'Test reminder for your upcoming appointment'
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
