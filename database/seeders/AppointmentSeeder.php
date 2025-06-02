<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use Carbon\Carbon;
use Faker\Factory as Faker;

class AppointmentSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Get doctors and patients (users with patient role)
        $doctors = User::whereHas('roles', function($query) {
            $query->where('code', 'doctor');
        })->with('doctor')->get();

        // ✅ FIXED: Get users with patient role (not Patient model directly)
        $patients = User::whereHas('roles', function($query) {
            $query->where('code', 'patient');
        })->get();

        if ($doctors->isEmpty() || $patients->isEmpty()) {
            $this->command->info('No doctors or patients found. Please run AppointmentTestUsersSeeder first.');
            return;
        }

        $appointmentTypes = ['consultation', 'follow-up', 'procedure', 'emergency', 'therapy'];
        $statuses = [
            Appointment::STATUS_SCHEDULED,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELLED_BY_PATIENT,
            Appointment::STATUS_NO_SHOW
        ];

        $reasons = [
            'Regular checkup',
            'Follow-up consultation',
            'Vaccination',
            'Blood test',
            'Physical examination',
            'Prescription renewal',
            'Health concern',
            'Routine screening',
            'Specialist consultation',
            'Treatment review'
        ];

        // Create appointments for the next 30 days
        $appointmentCount = 0;
        $maxAppointments = 100;

        for ($day = 0; $day < 30 && $appointmentCount < $maxAppointments; $day++) {
            $appointmentDate = Carbon::today()->addDays($day);
            
            // Skip weekends for most appointments
            if ($appointmentDate->isWeekend() && rand(1, 10) > 3) {
                continue;
            }

            // Create 1-8 appointments per day
            $dailyAppointments = rand(1, 8);

            for ($i = 0; $i < $dailyAppointments && $appointmentCount < $maxAppointments; $i++) {
                $doctor = $doctors->random();
                $patient = $patients->random(); // This is a User with patient role

                // ✅ GENERATE: Appointment time using doctor's working hours
                $workingHours = $doctor->doctor->working_hours ?? [];
                $dayName = strtolower($appointmentDate->format('l'));
                
                // Check if doctor works on this day
                if (!isset($workingHours[$dayName]) || $workingHours[$dayName] === null) {
                    continue;
                }

                $daySchedule = $workingHours[$dayName];
                $startTime = $daySchedule[0]; // "09:00"
                $endTime = $daySchedule[1];   // "17:00"

                // Generate random appointment time within working hours
                $workStartHour = (int)explode(':', $startTime)[0];
                $workEndHour = (int)explode(':', $endTime)[0];
                
                $appointmentHour = rand($workStartHour, min($workEndHour - 1, 16));
                $appointmentMinute = [0, 30][rand(0, 1)]; // 30-minute slots
                
                $appointmentStart = $appointmentDate->copy()->setTime($appointmentHour, $appointmentMinute);
                $appointmentEnd = $appointmentStart->copy()->addMinutes(30);

                // Skip lunch time (12:00-13:00)
                if ($appointmentHour >= 12 && $appointmentHour < 13) {
                    continue;
                }

                // Check for conflicts with existing appointments
                $conflict = Appointment::where('doctor_user_id', $doctor->id)
                    ->where('appointment_datetime_start', '<', $appointmentEnd)
                    ->where('appointment_datetime_end', '>', $appointmentStart)
                    ->exists();

                if ($conflict) {
                    continue; // Skip if there's a conflict
                }

                // Determine status based on appointment date
                $status = Appointment::STATUS_SCHEDULED;
                if ($appointmentStart->isPast()) {
                    $status = $faker->randomElement([
                        Appointment::STATUS_COMPLETED,
                        Appointment::STATUS_COMPLETED,
                        Appointment::STATUS_COMPLETED, // Higher chance of completed
                        Appointment::STATUS_NO_SHOW,
                        Appointment::STATUS_CANCELLED_BY_PATIENT
                    ]);
                } elseif ($appointmentStart->isToday() || $appointmentStart->isTomorrow()) {
                    $status = $faker->randomElement([
                        Appointment::STATUS_SCHEDULED,
                        Appointment::STATUS_CONFIRMED,
                        Appointment::STATUS_CONFIRMED // Higher chance of confirmed for near dates
                    ]);
                }

                // ✅ CREATE: Appointment using patient User ID (not Patient model ID)
                $appointment = Appointment::create([
                    'patient_user_id' => $patient->id, // User ID with patient role
                    'doctor_user_id' => $doctor->id,   // User ID with doctor role
                    'appointment_datetime_start' => $appointmentStart,
                    'appointment_datetime_end' => $appointmentEnd,
                    'type' => $faker->randomElement($appointmentTypes),
                    'reason_for_visit' => $faker->randomElement($reasons),
                    'status' => $status,
                    'notes_by_patient' => $faker->optional(0.3)->sentence(),
                    'notes_by_staff' => $status === Appointment::STATUS_COMPLETED ? 
                        $faker->optional(0.7)->sentence() : null,
                    'booked_by_user_id' => $faker->randomElement([$patient->id, $doctor->id]),
                    'last_updated_by_user_id' => $doctor->id,
                    'reminder_sent' => $appointmentStart->isFuture() ? $faker->boolean(30) : true,
                    'reminder_sent_at' => $appointmentStart->isFuture() && $faker->boolean(30) ? 
                        $appointmentStart->copy()->subDay() : null,
                    'created_at' => $appointmentStart->copy()->subDays(rand(1, 7)),
                    'updated_at' => now(),
                ]);

                // Add cancellation reason for cancelled appointments
                if (in_array($status, [
                    Appointment::STATUS_CANCELLED_BY_PATIENT,
                    Appointment::STATUS_CANCELLED_BY_CLINIC
                ])) {
                    $appointment->update([
                        'cancellation_reason' => $faker->randomElement([
                            'Patient unavailable',
                            'Emergency situation',
                            'Illness',
                            'Schedule conflict',
                            'Personal reasons',
                            'Transportation issues'
                        ])
                    ]);
                }

                $appointmentCount++;
            }
        }

        $this->command->info("Created {$appointmentCount} appointments successfully!");
    }
}