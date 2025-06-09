<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;

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
    public function handle()
    {
        $appointmentId = $this->argument('appointment_id') ?? 70;
        
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) {
            $this->error("Appointment {$appointmentId} not found!");
            return 1;
        }

        $this->info("Testing reminder email for appointment {$appointmentId}...");
        $this->info("Patient: {$appointment->patient->name} ({$appointment->patient->email})");
        $this->info("Doctor: {$appointment->doctor->name}");
        $this->info("Date: {$appointment->appointment_datetime_start}");

        // Dispatch the reminder job immediately (no delay)
        $job = SendAppointmentReminder::dispatch(
            $appointment->id,
            $appointment->patient_user_id,
            'email',
            '24h',
            [
                'type' => '24h',
                'priority' => 'test',
                'test_mode' => true
            ]
        );

        $this->info("✅ Reminder email job dispatched successfully!");
        $this->info("Check your email logs and the patient's email inbox.");
        
        return 0;
    }
}
