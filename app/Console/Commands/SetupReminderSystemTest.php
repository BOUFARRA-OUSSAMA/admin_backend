<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Appointment;
use App\Services\ReminderService;
use App\Services\ReminderNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SetupReminderSystemTest extends Command
{
    protected $signature = 'reminder:setup-test {--create-users : Create test users if they don\'t exist}';
    protected $description = 'Set up a complete test environment for the ASIO reminder system';

    protected ReminderService $reminderService;
    protected ReminderNotificationService $notificationService;

    public function __construct(
        ReminderService $reminderService,
        ReminderNotificationService $notificationService
    ) {
        parent::__construct();
        $this->reminderService = $reminderService;
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('🏥 ASIO Reminder System - Complete Test Setup');
        $this->info('============================================');        try {
            // Step 1: Check/Create test users
            $users = $this->setupTestUsers();

            // Step 2: Create test appointments
            $appointments = $this->createTestAppointments($users);

            // Step 3: Verify reminder scheduling
            $this->verifyReminderScheduling($appointments);

            // Step 4: Test email functionality
            $this->testEmailFunctionality($appointments[0]);

            // Step 5: Display API testing instructions
            $this->displayApiTestingInstructions($users);

            $this->info('✅ ASIO Reminder System test setup completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }    protected function setupTestUsers()
    {
        $this->info('👥 Setting up test users...');

        $users = [];

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@asio.com'],
            [
                'name' => 'ASIO Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'phone' => '+1234567890',
                'status' => 'active'
            ]
        );
        $users['admin'] = $admin;
        $this->line("  ✓ Admin: {$admin->email}");

        // Create test doctor
        $doctor = User::firstOrCreate(
            ['email' => 'dr.smith@asio.com'],
            [
                'name' => 'Dr. John Smith',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'phone' => '+1234567891',
                'status' => 'active'
            ]
        );
        $users['doctor'] = $doctor;
        $this->line("  ✓ Doctor: {$doctor->email}");

        // Create test patient
        $patient = User::firstOrCreate(
            ['email' => 'patient@asio.com'],
            [
                'name' => 'Jane Doe',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'phone' => '+1234567892',
                'status' => 'active'
            ]
        );
        $users['patient'] = $patient;
        $this->line("  ✓ Patient: {$patient->email}");

        return $users;
    }

    protected function createTestAppointments($users)
    {
        $this->info('📅 Creating test appointments...');

        $appointments = [];

        // Appointment 1: Tomorrow at 10:00 AM (should trigger 24h reminder)
        $appointment1 = Appointment::create([
            'patient_user_id' => $users['patient']->id,
            'doctor_user_id' => $users['doctor']->id,
            'appointment_datetime_start' => Carbon::tomorrow()->setTime(10, 0),
            'appointment_datetime_end' => Carbon::tomorrow()->setTime(10, 30),
            'type' => 'consultation',
            'reason_for_visit' => 'Regular checkup and health screening',
            'status' => Appointment::STATUS_SCHEDULED,
            'booked_by_user_id' => $users['admin']->id,
            'notes_by_patient' => 'Looking forward to my appointment',
            'notes_by_staff' => 'New patient - first visit'
        ]);
        $appointments[] = $appointment1;
        $this->line("  ✓ Appointment 1: Tomorrow at 10:00 AM");

        // Appointment 2: Day after tomorrow at 2:00 PM
        $appointment2 = Appointment::create([
            'patient_user_id' => $users['patient']->id,
            'doctor_user_id' => $users['doctor']->id,
            'appointment_datetime_start' => Carbon::tomorrow()->addDay()->setTime(14, 0),
            'appointment_datetime_end' => Carbon::tomorrow()->addDay()->setTime(14, 30),
            'type' => 'follow-up',
            'reason_for_visit' => 'Follow-up consultation for blood test results',
            'status' => Appointment::STATUS_SCHEDULED,
            'booked_by_user_id' => $users['admin']->id,
            'notes_by_patient' => 'Need to discuss test results',
            'notes_by_staff' => 'Review lab results from last week'
        ]);
        $appointments[] = $appointment2;
        $this->line("  ✓ Appointment 2: Day after tomorrow at 2:00 PM");

        // Appointment 3: In 2 hours (should trigger 2h reminder immediately)
        $appointment3 = Appointment::create([
            'patient_user_id' => $users['patient']->id,
            'doctor_user_id' => $users['doctor']->id,
            'appointment_datetime_start' => Carbon::now()->addHours(2),
            'appointment_datetime_end' => Carbon::now()->addHours(2)->addMinutes(30),
            'type' => 'urgent',
            'reason_for_visit' => 'Urgent consultation for test results',
            'status' => Appointment::STATUS_SCHEDULED,
            'booked_by_user_id' => $users['admin']->id,
            'notes_by_patient' => 'Urgent appointment needed',
            'notes_by_staff' => 'Priority appointment - urgent case'
        ]);
        $appointments[] = $appointment3;
        $this->line("  ✓ Appointment 3: In 2 hours (urgent)");

        return $appointments;
    }    protected function verifyReminderScheduling($appointments)
    {
        $this->info('⏰ Verifying automatic reminder scheduling...');
        $this->line('   💡 Note: Reminders are automatically scheduled by AppointmentObserver when appointments are created');
        $this->line('');

        foreach ($appointments as $index => $appointment) {
            $this->line("  📋 Appointment " . ($index + 1) . ":");
            $this->line("     Patient: {$appointment->patient->name}");
            $this->line("     Doctor: {$appointment->doctor->name}");
            $this->line("     Time: {$appointment->appointment_datetime_start->format('Y-m-d H:i')}");
            
            // Check scheduled reminder jobs (the new way)
            $scheduledJobs = \App\Models\ScheduledReminderJob::where('appointment_id', $appointment->id)
                ->orderBy('scheduled_for', 'asc')
                ->get();
            $this->line("     Scheduled reminder jobs: {$scheduledJobs->count()}");
            
            foreach ($scheduledJobs as $job) {
                $status = $job->status === 'pending' ? '⏳ ' . $job->status : '✅ ' . $job->status;
                $this->line("       - {$job->reminder_type} via {$job->channel}: {$job->scheduled_for->format('Y-m-d H:i')} ({$status})");
            }
            
            // Check reminder logs (if any have been sent)
            $sentReminders = \App\Models\ReminderLog::where('appointment_id', $appointment->id)->count();
            if ($sentReminders > 0) {
                $this->line("     📧 Reminders already sent: {$sentReminders}");
            }
            
            $this->line('');
        }
    }protected function testEmailFunctionality($appointment)
    {
        $this->info('📧 Testing email functionality...');

        try {
            // Check scheduled reminders (created automatically by observer)
            $scheduledReminders = \App\Models\ScheduledReminderJob::where('appointment_id', $appointment->id)
                ->where('status', 'pending')
                ->orderBy('scheduled_for', 'asc')
                ->get();
            
            if ($scheduledReminders->count() > 0) {
                $this->line("  ✅ Found {$scheduledReminders->count()} scheduled reminders (automatic)");
                
                foreach ($scheduledReminders as $reminder) {
                    $this->line("     - {$reminder->reminder_type} via {$reminder->channel}: {$reminder->scheduled_for->format('Y-m-d H:i')}");
                }
                
                $this->line("  ⏰ Reminders will be sent automatically at scheduled times");
                $this->line("  💡 Note: Appointment observer automatically schedules reminders on creation");
                
            } else {
                $this->line("  ⚠️  No scheduled reminders found");
                
                // Send a manual test email if no automatic reminders
                $this->line("  📧 Sending manual test email...");
                $result = $this->notificationService->sendReminder(
                    $appointment,
                    $appointment->patient,
                    'email',
                    'test',
                    [
                        'priority' => 'test',
                        'test_mode' => true,
                        'custom_message' => 'This is a manual test email from the setup command'
                    ]
                );
                
                if ($result['success']) {
                    $this->line("  ✅ Manual test email sent successfully!");
                    $this->line("     Recipient: {$appointment->patient->email}");
                } else {
                    $this->line("  ❌ Manual test email failed: {$result['error']}");
                }
            }

        } catch (\Exception $e) {
            $this->line("  ❌ Email test failed: {$e->getMessage()}");
        }
    }

    protected function displayApiTestingInstructions($users)
    {
        $this->info('🔗 API Testing Instructions');
        $this->info('==========================');
        
        $this->line('1. First, authenticate to get a JWT token:');
        $this->line('   POST /api/auth/login');
        $this->line('   Body: {');
        $this->line('     "email": "admin@asio.com",');
        $this->line('     "password": "password123"');
        $this->line('   }');
        $this->line('');
        
        $this->line('2. Use the token in Authorization header for subsequent requests:');
        $this->line('   Authorization: Bearer YOUR_JWT_TOKEN');
        $this->line('');
        
        $this->line('3. Create a new appointment (triggers automatic reminders):');
        $this->line('   POST /api/appointments');
        $this->line('   Body: {');
        $this->line('     "patient_id": ' . $users['patient']->id . ',');
        $this->line('     "doctor_id": ' . $users['doctor']->id . ',');
        $this->line('     "appointment_datetime_start": "' . Carbon::tomorrow()->addHours(3)->format('Y-m-d H:i:s') . '",');
        $this->line('     "reason": "API Test Appointment",');
        $this->line('     "type": "consultation",');
        $this->line('     "patient_notes": "Created via API test"');
        $this->line('   }');
        $this->line('');
        
        $this->line('4. Check appointment reminders:');
        $this->line('   GET /api/appointments/{appointment_id}');
        $this->line('');
        
        $this->line('5. Manual reminder testing commands:');
        $this->line('   php artisan reminder:test-email');
        $this->line('   php artisan reminder:send-scheduled');
        $this->line('');
          $this->line('📊 Database Status:');
        $this->line('   Appointments: ' . Appointment::count());
        $this->line('   Scheduled Reminder Jobs: ' . \App\Models\ScheduledReminderJob::count());
        $this->line('   Reminder Logs: ' . \App\Models\ReminderLog::count());
        $this->line('');
        
        $this->line('🔄 Queue Status:');
        $pendingJobs = \App\Models\ScheduledReminderJob::where('status', 'pending')->count();
        $processedJobs = \App\Models\ScheduledReminderJob::whereIn('status', ['sent', 'failed'])->count();
        $this->line("   Pending reminder jobs: {$pendingJobs}");
        $this->line("   Processed reminder jobs: {$processedJobs}");
        $this->line('');
        
        $this->line('💡 Important Notes:');
        $this->line('   • Reminders are automatically scheduled when appointments are created');
        $this->line('   • The AppointmentObserver handles this process');
        $this->line('   • No need to manually schedule reminders in most cases');
        $this->line('   • Queue workers will process scheduled reminders at the right time');
    }
}
