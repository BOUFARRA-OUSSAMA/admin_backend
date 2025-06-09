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
use Spatie\Permission\Models\Role;

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
        $this->info('============================================');

        try {
            // Step 1: Check/Create roles
            $this->setupRoles();

            // Step 2: Check/Create test users
            $users = $this->setupTestUsers();

            // Step 3: Create test appointments
            $appointments = $this->createTestAppointments($users);

            // Step 4: Verify reminder scheduling
            $this->verifyReminderScheduling($appointments);

            // Step 5: Test email functionality
            $this->testEmailFunctionality($appointments[0]);

            // Step 6: Display API testing instructions
            $this->displayApiTestingInstructions($users);

            $this->info('✅ ASIO Reminder System test setup completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    protected function setupRoles()
    {
        $this->info('📋 Setting up roles...');

        $roles = ['admin', 'doctor', 'patient', 'receptionist'];
        
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $this->line("  ✓ Role '{$roleName}' ready");
        }
    }

    protected function setupTestUsers()
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
        $admin->assignRole('admin');
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
        $doctor->assignRole('doctor');
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
        $patient->assignRole('patient');
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
    }

    protected function verifyReminderScheduling($appointments)
    {
        $this->info('⏰ Verifying reminder scheduling...');

        foreach ($appointments as $index => $appointment) {
            $this->line("  📋 Appointment " . ($index + 1) . ":");
            $this->line("     Patient: {$appointment->patient->name}");
            $this->line("     Doctor: {$appointment->doctor->name}");
            $this->line("     Time: {$appointment->appointment_datetime_start->format('Y-m-d H:i')}");
            
            // Check scheduled reminders
            $reminders = $appointment->reminders;
            $this->line("     Reminders scheduled: {$reminders->count()}");
            
            foreach ($reminders as $reminder) {
                $this->line("       - {$reminder->reminder_type}: {$reminder->scheduled_at->format('Y-m-d H:i')} (Status: {$reminder->status})");
            }
            $this->line('');
        }
    }

    protected function testEmailFunctionality($appointment)
    {
        $this->info('📧 Testing email functionality...');

        try {
            // Get the first reminder for this appointment
            $reminder = $appointment->reminders->first();
            
            if ($reminder) {
                $this->line("  Sending test email for {$reminder->reminder_type} reminder...");
                
                $result = $this->notificationService->sendReminder($reminder);
                
                if ($result['success']) {
                    $this->line("  ✅ Email sent successfully!");
                    $this->line("     Recipient: {$appointment->patient->email}");
                    $this->line("     Subject: {$result['subject']}");
                } else {
                    $this->line("  ❌ Email failed: {$result['error']}");
                }
            } else {
                $this->line("  ⚠️  No reminders found for testing");
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
        $this->line('   Scheduled Reminders: ' . \App\Models\AppointmentReminder::count());
        $this->line('   Reminder Logs: ' . \App\Models\ReminderLog::count());
    }
}
