<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentCancelled;
use App\Notifications\PaymentDue;
use App\Notifications\LabResultsReady;
use App\Notifications\PrescriptionReady;
use App\Notifications\SystemMaintenance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Faker\Factory as Faker;

class NotificationTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('🔔 Creating comprehensive notification test data...');
        
        $faker = Faker::create();
        
        // Get patient users (first 10 for focused testing)
        $patients = User::whereHas('roles', function($q) {
            $q->where('code', 'patient');
        })->take(10)->get();
        
        if ($patients->isEmpty()) {
            $this->command->error('No patients found. Please run patient seeders first.');
            return;
        }
        
        // Get first patient for focused testing
        $testPatient = $patients->first();
        $this->command->info("Creating notifications for test patient: {$testPatient->name} ({$testPatient->email})");
        
        // 1. Create various notification types for the test patient
        $this->createNotifications($testPatient, $faker);
        
        // 2. Create upcoming appointments with reminders for all patients
        $this->createUpcomingAppointments($patients, $faker);
        
        // 3. Create some notifications for other patients too (for pagination testing)
        $this->createBulkNotifications($patients, $faker);
        
        $this->command->info('✅ Notification test data created successfully!');
        $this->command->info('🎯 Test patient login: ' . $testPatient->email . ' (password: password)');
    }
    
    /**
     * Create various notification types for a specific patient
     */
    private function createNotifications(User $patient, $faker)
    {
        $this->command->info('📧 Creating diverse notifications...');
        
        $notifications = [
            // Appointment Reminders (various times)
            [
                'type' => 'appointment_reminder',
                'title' => 'Appointment Reminder - Tomorrow at 2:30 PM',
                'message' => 'Don\'t forget your appointment tomorrow at 2:30 PM with Dr. Sarah Johnson.',
                'action_url' => '/appointments/123',
                'priority' => 'high',
                'created_at' => now()->subHours(2),
                'read_at' => null,
            ],
            [
                'type' => 'appointment_reminder',
                'title' => 'Upcoming Appointment in 2 Hours',
                'message' => 'Your appointment with Dr. Michael Chen is in 2 hours at 10:00 AM.',
                'action_url' => '/appointments/124',
                'priority' => 'urgent',
                'created_at' => now()->subMinutes(30),
                'read_at' => null,
            ],
            
            // Appointment Status Updates
            [
                'type' => 'appointment_confirmed',
                'title' => 'Appointment Confirmed',
                'message' => 'Your appointment on June 15th at 3:00 PM has been confirmed.',
                'action_url' => '/appointments/125',
                'priority' => 'medium',
                'created_at' => now()->subDays(1),
                'read_at' => now()->subHours(12), // This one is read
            ],
            [
                'type' => 'appointment_cancelled',
                'title' => 'Appointment Cancelled',
                'message' => 'Your appointment on June 12th has been cancelled. Please reschedule.',
                'action_url' => '/appointments/book',
                'priority' => 'high',
                'created_at' => now()->subDays(2),
                'read_at' => null,
            ],
            [
                'type' => 'appointment_rescheduled',
                'title' => 'Appointment Rescheduled',
                'message' => 'Your appointment has been moved to June 20th at 11:00 AM.',
                'action_url' => '/appointments/126',
                'priority' => 'medium',
                'created_at' => now()->subHours(6),
                'read_at' => null,
            ],
            
            // Medical/Clinical Notifications
            [
                'type' => 'lab_results_ready',
                'title' => 'Lab Results Available',
                'message' => 'Your blood test results are now available. Please review them in your portal.',
                'action_url' => '/lab-results/789',
                'priority' => 'medium',
                'created_at' => now()->subDays(3),
                'read_at' => null,
            ],
            [
                'type' => 'prescription_ready',
                'title' => 'Prescription Ready for Pickup',
                'message' => 'Your prescription for Metformin is ready for pickup at the pharmacy.',
                'action_url' => '/prescriptions/456',
                'priority' => 'medium',
                'created_at' => now()->subHours(4),
                'read_at' => null,
            ],
            [
                'type' => 'test_scheduled',
                'title' => 'Blood Test Scheduled',
                'message' => 'Your blood test has been scheduled for June 18th at 9:00 AM.',
                'action_url' => '/tests/scheduled',
                'priority' => 'medium',
                'created_at' => now()->subDays(1),
                'read_at' => now()->subHours(8), // Read
            ],
            
            // Billing/Payment Notifications
            [
                'type' => 'payment_due',
                'title' => 'Payment Due - $175.00',
                'message' => 'You have an outstanding balance of $175.00 for your recent consultation.',
                'action_url' => '/billing/payments',
                'priority' => 'high',
                'created_at' => now()->subDays(5),
                'read_at' => null,
            ],
            [
                'type' => 'payment_received',
                'title' => 'Payment Received',
                'message' => 'Thank you! Your payment of $200.00 has been processed successfully.',
                'action_url' => '/billing/history',
                'priority' => 'low',
                'created_at' => now()->subDays(7),
                'read_at' => now()->subDays(6), // Read
            ],
            [
                'type' => 'insurance_claim',
                'title' => 'Insurance Claim Update',
                'message' => 'Your insurance claim for the June 5th visit has been approved.',
                'action_url' => '/billing/claims',
                'priority' => 'medium',
                'created_at' => now()->subDays(4),
                'read_at' => null,
            ],
            
            // Health/Wellness Reminders
            [
                'type' => 'medication_reminder',
                'title' => 'Take Your Medication',
                'message' => 'Don\'t forget to take your evening dose of Lisinopril.',
                'action_url' => '/medications',
                'priority' => 'medium',
                'created_at' => now()->subHours(1),
                'read_at' => null,
            ],
            [
                'type' => 'checkup_due',
                'title' => 'Annual Checkup Due',
                'message' => 'It\'s time for your annual health checkup. Please schedule an appointment.',
                'action_url' => '/appointments/book',
                'priority' => 'medium',
                'created_at' => now()->subDays(10),
                'read_at' => null,
            ],
            [
                'type' => 'vaccination_reminder',
                'title' => 'Flu Vaccination Reminder',
                'message' => 'Your annual flu vaccination is recommended. Book your appointment today.',
                'action_url' => '/appointments/book?type=vaccination',
                'priority' => 'medium',
                'created_at' => now()->subDays(8),
                'read_at' => null,
            ],
            
            // System/Administrative
            [
                'type' => 'system_maintenance',
                'title' => 'Scheduled Maintenance',
                'message' => 'The patient portal will be unavailable on June 25th from 2:00 AM to 4:00 AM.',
                'action_url' => null,
                'priority' => 'low',
                'created_at' => now()->subDays(6),
                'read_at' => null,
            ],
            [
                'type' => 'profile_update',
                'title' => 'Please Update Your Profile',
                'message' => 'Please review and update your contact information and emergency contacts.',
                'action_url' => '/profile/edit',
                'priority' => 'medium',
                'created_at' => now()->subDays(12),
                'read_at' => null,
            ],
            [
                'type' => 'survey_request',
                'title' => 'Your Feedback Matters',
                'message' => 'Please take 2 minutes to rate your recent visit with Dr. Johnson.',
                'action_url' => '/feedback/survey/123',
                'priority' => 'low',
                'created_at' => now()->subDays(3),
                'read_at' => null,
            ],
        ];
        
        // Create notifications using Laravel's notification system
        foreach ($notifications as $index => $notificationData) {
            // Create notification in database
            $patient->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\InAppNotification',
                'data' => [
                    'type' => $notificationData['type'],
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'action_url' => $notificationData['action_url'],
                    'priority' => $notificationData['priority'],
                ],
                'read_at' => $notificationData['read_at'],
                'created_at' => $notificationData['created_at'],
                'updated_at' => $notificationData['created_at'],
            ]);
            
            $this->command->line("  ✓ Created: {$notificationData['title']}");
        }
        
        $this->command->info("✅ Created " . count($notifications) . " notifications for test patient");
    }
    
    /**
     * Create upcoming appointments that will show in reminders tab
     */
    private function createUpcomingAppointments($patients, $faker)
    {
        $this->command->info('📅 Creating upcoming appointments for reminders...');
        
        // Get available doctors
        $doctors = User::whereHas('roles', function($q) {
            $q->where('code', 'doctor');
        })->take(3)->get();
        
        if ($doctors->isEmpty()) {
            $this->command->warning('No doctors found for appointment creation');
            return;
        }
        
        $appointmentTypes = ['consultation', 'follow-up', 'checkup', 'procedure', 'therapy'];
        $reasons = [
            'Regular health checkup',
            'Follow-up consultation',
            'Blood pressure monitoring',
            'Diabetes management',
            'Physical therapy session',
            'Vaccination appointment',
            'Lab test review',
            'Medication adjustment',
            'Specialist consultation',
            'Preventive care visit'
        ];
        
        // Create appointments for the next 30 days
        foreach ($patients as $patient) {
            // Each patient gets 1-3 upcoming appointments
            $appointmentCount = rand(1, 3);
            
            for ($i = 0; $i < $appointmentCount; $i++) {
                $doctor = $doctors->random();
                
                // Create appointment in the future (next 1-30 days)
                $appointmentDate = now()->addDays(rand(1, 30));
                $appointmentHour = rand(9, 16); // 9 AM to 4 PM
                $appointmentMinute = [0, 30][rand(0, 1)]; // 30-minute slots
                
                $appointmentStart = $appointmentDate->setTime($appointmentHour, $appointmentMinute);
                $appointmentEnd = $appointmentStart->copy()->addMinutes(30);
                
                // Skip weekends
                if ($appointmentStart->isWeekend()) {
                    continue;
                }
                
                // Check for conflicts
                $conflict = Appointment::where('doctor_user_id', $doctor->id)
                    ->where('appointment_datetime_start', '<', $appointmentEnd)
                    ->where('appointment_datetime_end', '>', $appointmentStart)
                    ->exists();
                
                if ($conflict) {
                    continue;
                }
                  // Create the appointment
                $appointment = Appointment::create([
                    'patient_user_id' => $patient->id,
                    'doctor_user_id' => $doctor->id,
                    'appointment_datetime_start' => $appointmentStart,
                    'appointment_datetime_end' => $appointmentEnd,
                    'type' => $faker->randomElement($appointmentTypes),
                    'reason_for_visit' => $faker->randomElement($reasons),
                    'status' => 'scheduled',
                    'notes_by_patient' => $faker->optional(0.3)->sentence(),
                    'booked_by_user_id' => $patient->id,
                    'reminder_sent' => false,
                ]);
                
                // Create scheduled reminder jobs for the appointment
                $this->createScheduledReminderJobs($appointment, $faker);
                
                // Create reminder notification for appointments in the next 7 days
                if ($appointmentStart->diffInDays(now()) <= 7) {
                    $reminderMessage = "Upcoming appointment on " . 
                        $appointmentStart->format('M j, Y \a\t g:i A') . 
                        " with " . $doctor->name;
                    
                    $patient->notifications()->create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'type' => 'App\\Notifications\\InAppNotification',
                        'data' => [
                            'type' => 'appointment_reminder',
                            'title' => 'Upcoming Appointment',
                            'message' => $reminderMessage,
                            'action_url' => '/appointments/' . $appointment->id,
                            'priority' => 'medium',
                            'appointment_id' => $appointment->id,
                        ],
                        'read_at' => null,
                        'created_at' => now()->subHours(rand(1, 24)),
                        'updated_at' => now()->subHours(rand(1, 24)),
                    ]);
                }
            }
        }
        
        $upcomingCount = Appointment::where('appointment_datetime_start', '>', now())->count();
        $this->command->info("✅ Created upcoming appointments (Total upcoming: {$upcomingCount})");
    }
    
    /**
     * Create notifications for other patients (for pagination testing)
     */
    private function createBulkNotifications($patients, $faker)
    {
        $this->command->info('📊 Creating bulk notifications for pagination testing...');
        
        $notificationTypes = [
            'appointment_reminder' => [
                'titles' => [
                    'Appointment Tomorrow',
                    'Don\'t Forget Your Appointment',
                    'Appointment in 2 Hours',
                    'Weekly Checkup Reminder',
                ],
                'priority' => 'high'
            ],
            'lab_results_ready' => [
                'titles' => [
                    'Lab Results Available',
                    'Blood Test Results Ready',
                    'X-Ray Results Ready',
                    'Urine Test Results',
                ],
                'priority' => 'medium'
            ],
            'payment_due' => [
                'titles' => [
                    'Payment Due - $150.00',
                    'Outstanding Balance',
                    'Payment Reminder',
                    'Invoice Due',
                ],
                'priority' => 'high'
            ],
            'medication_reminder' => [
                'titles' => [
                    'Take Your Morning Medication',
                    'Evening Dose Reminder',
                    'Prescription Refill Due',
                    'Medication Schedule',
                ],
                'priority' => 'medium'
            ],
            'system_announcement' => [
                'titles' => [
                    'New Features Available',
                    'Holiday Hours Notice',
                    'Portal Maintenance',
                    'Important Update',
                ],
                'priority' => 'low'
            ],
        ];
        
        // Create 5-10 notifications for each patient (except the first one)
        foreach ($patients->skip(1) as $patient) {
            $notificationCount = rand(5, 10);
            
            for ($i = 0; $i < $notificationCount; $i++) {
                $type = $faker->randomKey($notificationTypes);
                $typeData = $notificationTypes[$type];
                $title = $faker->randomElement($typeData['titles']);
                
                $createdAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));
                $isRead = $faker->boolean(30); // 30% chance of being read
                
                $patient->notifications()->create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'App\\Notifications\\InAppNotification',
                    'data' => [
                        'type' => $type,
                        'title' => $title,
                        'message' => $faker->sentence(rand(10, 20)),
                        'action_url' => $faker->optional(0.7)->randomElement([
                            '/appointments',
                            '/lab-results',
                            '/billing',
                            '/medications',
                            '/profile'
                        ]),
                        'priority' => $typeData['priority'],
                    ],
                    'read_at' => $isRead ? $createdAt->copy()->addHours(rand(1, 24)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
        
        $totalNotifications = \DB::table('notifications')->count();
        $unreadCount = \DB::table('notifications')->whereNull('read_at')->count();
          $this->command->info("✅ Bulk notifications created");
        $this->command->info("📊 Total notifications: {$totalNotifications}");
        $this->command->info("📧 Unread notifications: {$unreadCount}");
    }    /**
     * Create scheduled reminder jobs for an appointment
     */
    private function createScheduledReminderJobs($appointment, $faker)
    {
        // Create multiple reminder jobs at different intervals before the appointment
        $reminderIntervals = [
            ['days' => 7, 'type' => '24h'],
            ['days' => 1, 'type' => '2h'],
            ['hours' => 2, 'type' => 'manual'],
        ];

        foreach ($reminderIntervals as $interval) {
            // Calculate when the reminder should be sent
            $scheduledFor = $appointment->appointment_datetime_start->copy();
            
            if (isset($interval['days'])) {
                $scheduledFor->subDays($interval['days']);
            } else {
                $scheduledFor->subHours($interval['hours']);
            }

            // Only create reminders that are in the future
            if ($scheduledFor->gt(now())) {
                \App\Models\ScheduledReminderJob::create([
                    'appointment_id' => $appointment->id,
                    'job_id' => \Illuminate\Support\Str::uuid(),
                    'reminder_type' => $interval['type'],
                    'channel' => $faker->randomElement(['email', 'sms', 'push']),
                    'scheduled_for' => $scheduledFor,
                    'status' => 'pending',
                    'job_payload' => json_encode([
                        'appointment_id' => $appointment->id,
                        'patient_id' => $appointment->patient_user_id,
                        'doctor_id' => $appointment->doctor_user_id,
                        'appointment_time' => $appointment->appointment_datetime_start->toISOString(),
                        'reminder_type' => $interval['type']
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
