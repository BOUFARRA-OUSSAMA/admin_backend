<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\ReminderNotificationService;

class TestReminderEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:reminder-email {appointment_id? : The appointment ID to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending reminder email for an appointment';

    /**
     * Execute the console command.
     */
    public function handle(ReminderNotificationService $notificationService)
    {
        $appointmentId = $this->argument('appointment_id') ?? 48;
        
        $appointment = Appointment::with(['patient', 'doctor'])->find($appointmentId);
        if (!$appointment) {
            $this->error("Appointment {$appointmentId} not found!");
            return 1;
        }

        $this->info("Testing reminder email for appointment {$appointmentId}...");
        $this->info("Patient: {$appointment->patient->name} ({$appointment->patient->email})");
        $this->info("Doctor: {$appointment->doctor->name}");
        $this->info("Date: {$appointment->appointment_datetime_start}");

        // Send reminder directly using the notification service
        $result = $notificationService->sendReminder(
            $appointment,
            $appointment->patient,
            'email',
            '24h',
            [
                'priority' => 'test',
                'test_mode' => true
            ]
        );

        if ($result['success']) {
            $this->info("✅ Reminder email sent successfully!");
            $this->info("Subject: " . ($result['subject'] ?? 'N/A'));
            $this->info("Message ID: " . ($result['message_id'] ?? 'N/A'));
        } else {
            $this->error("❌ Failed to send reminder email: " . ($result['error'] ?? 'Unknown error'));
            return 1;
        }
        
        return 0;
    }
}
