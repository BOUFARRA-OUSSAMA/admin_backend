<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PersonalInfo;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AppointmentTestUsersSeeder extends Seeder
{
    public function run()
    {
        // Get roles
        $doctorRole = Role::where('code', 'doctor')->first();
        $patientRole = Role::where('code', 'patient')->first();

        if (!$doctorRole || !$patientRole) {
            $this->command->error('Roles not found. Please run RoleSeeder first.');
            return;
        }

        // ✅ UPDATED: Create test doctors with appointment limits
        $doctors = [
            [
                'name' => 'Dr. Sarah Johnson',
                'email' => 'dr.johnson@clinic.com',
                'phone' => '555-0101',
                'specialty' => 'General Medicine',
                'license_number' => 'MD12345',
                'experience_years' => 15,
                'consultation_fee' => 150.00,
                'max_patient_appointments' => 5, // ✅ Standard limit
                'working_hours' => [
                    'monday' => ['09:00', '17:00'],
                    'tuesday' => ['09:00', '17:00'],
                    'wednesday' => ['09:00', '17:00'],
                    'thursday' => ['09:00', '17:00'],
                    'friday' => ['09:00', '15:00'],
                    'saturday' => ['09:00', '13:00'],
                    'sunday' => null
                ]
            ],
            [
                'name' => 'Dr. Michael Chen',
                'email' => 'dr.chen@clinic.com',
                'phone' => '555-0102',
                'specialty' => 'Cardiology',
                'license_number' => 'MD12346',
                'experience_years' => 20,
                'consultation_fee' => 200.00,
                'max_patient_appointments' => 8, // ✅ Higher limit for specialist
                'working_hours' => [
                    'monday' => ['08:00', '16:00'],
                    'tuesday' => ['08:00', '16:00'],
                    'wednesday' => ['08:00', '16:00'],
                    'thursday' => ['08:00', '16:00'],
                    'friday' => ['08:00', '14:00'],
                    'saturday' => null,
                    'sunday' => null
                ]
            ],
            [
                'name' => 'Dr. Emily Rodriguez',
                'email' => 'dr.rodriguez@clinic.com',
                'phone' => '555-0103',
                'specialty' => 'Pediatrics',
                'license_number' => 'MD12347',
                'experience_years' => 12,
                'consultation_fee' => 175.00,
                'max_patient_appointments' => 3, // ✅ Lower limit for high-demand pediatrician
                'working_hours' => [
                    'monday' => ['10:00', '18:00'],
                    'tuesday' => ['10:00', '18:00'],
                    'wednesday' => ['10:00', '18:00'],
                    'thursday' => ['10:00', '18:00'],
                    'friday' => ['10:00', '16:00'],
                    'saturday' => ['10:00', '14:00'],
                    'sunday' => null
                ]
            ]
        ];

        foreach ($doctors as $doctorData) {
            // Create user
            $user = User::updateOrCreate(
                ['email' => $doctorData['email']],
                [
                    'name' => $doctorData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $doctorData['phone'],
                    'status' => 'active',
                ]
            );

            // Assign doctor role
            $user->roles()->sync([$doctorRole->id]);

            // ✅ UPDATED: Create doctor profile with appointment limits
            Doctor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'specialty' => $doctorData['specialty'],
                    'license_number' => $doctorData['license_number'],
                    'experience_years' => $doctorData['experience_years'],
                    'consultation_fee' => $doctorData['consultation_fee'],
                    'is_available' => true,
                    'working_hours' => $doctorData['working_hours'],
                    'max_patient_appointments' => $doctorData['max_patient_appointments'], // ✅ ADD THIS
                ]
            );
        }

        // ✅ CORRECT: Create patients following User -> Patient -> PersonalInfo architecture
        $patients = [
            [
                'user' => [
                    'name' => 'John Smith',
                    'email' => 'john.smith@email.com',
                    'phone' => '555-1001',
                ],
                'personal_info' => [
                    'name' => 'John',
                    'surname' => 'Smith',
                    'birthdate' => '1985-06-15',
                    'gender' => 'male',
                    'address' => '123 Main St, Cityville, ST 12345',
                    'emergency_contact' => 'Jane Smith - 555-9001',
                    'marital_status' => 'married',
                    'blood_type' => 'O+',
                    'nationality' => 'American',
                ]
            ],
            [
                'user' => [
                    'name' => 'Mary Johnson',
                    'email' => 'mary.johnson@email.com',
                    'phone' => '555-1002',
                ],
                'personal_info' => [
                    'name' => 'Mary',
                    'surname' => 'Johnson',
                    'birthdate' => '1990-03-22',
                    'gender' => 'female',
                    'address' => '456 Oak Ave, Townsburg, ST 23456',
                    'emergency_contact' => 'Tom Johnson - 555-9002',
                    'marital_status' => 'single',
                    'blood_type' => 'A+',
                    'nationality' => 'American',
                ]
            ],
            [
                'user' => [
                    'name' => 'Robert Wilson',
                    'email' => 'robert.wilson@email.com',
                    'phone' => '555-1003',
                ],
                'personal_info' => [
                    'name' => 'Robert',
                    'surname' => 'Wilson',
                    'birthdate' => '1978-11-08',
                    'gender' => 'male',
                    'address' => '789 Pine St, Villageton, ST 34567',
                    'emergency_contact' => 'Linda Wilson - 555-9003',
                    'marital_status' => 'married',
                    'blood_type' => 'B+',
                    'nationality' => 'American',
                ]
            ],
            [
                'user' => [
                    'name' => 'Lisa Davis',
                    'email' => 'lisa.davis@email.com',
                    'phone' => '555-1004',
                ],
                'personal_info' => [
                    'name' => 'Lisa',
                    'surname' => 'Davis',
                    'birthdate' => '1995-09-12',
                    'gender' => 'female',
                    'address' => '321 Elm St, Hamletville, ST 45678',
                    'emergency_contact' => 'Mark Davis - 555-9004',
                    'marital_status' => 'single',
                    'blood_type' => 'AB+',
                    'nationality' => 'American',
                ]
            ],
            [
                'user' => [
                    'name' => 'David Brown',
                    'email' => 'david.brown@email.com',
                    'phone' => '555-1005',
                ],
                'personal_info' => [
                    'name' => 'David',
                    'surname' => 'Brown',
                    'birthdate' => '1982-01-25',
                    'gender' => 'male',
                    'address' => '654 Maple Dr, Borough City, ST 56789',
                    'emergency_contact' => 'Sarah Brown - 555-9005',
                    'marital_status' => 'divorced',
                    'blood_type' => 'O-',
                    'nationality' => 'American',
                ]
            ]
        ];

        foreach ($patients as $patientData) {
            // Step 1: Create User
            $user = User::updateOrCreate(
                ['email' => $patientData['user']['email']],
                [
                    'name' => $patientData['user']['name'],
                    'password' => Hash::make('password'),
                    'phone' => $patientData['user']['phone'],
                    'status' => 'active',
                ]
            );

            // Step 2: Assign patient role
            $user->roles()->sync([$patientRole->id]);

            // Step 3: Create Patient record
            $patient = Patient::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'registration_date' => Carbon::now()->subDays(rand(30, 365)),
                ]
            );

            // Step 4: Create PersonalInfo linked to Patient
            PersonalInfo::updateOrCreate(
                ['patient_id' => $patient->id],
                $patientData['personal_info']
            );
        }

        $this->command->info('Created test doctors and patients successfully!');
        $this->command->info('Architecture: User -> Patient -> PersonalInfo');
        $this->command->info('');
        $this->command->info('🩺 DOCTOR APPOINTMENT LIMITS:');
        $this->command->info('  • Dr. Sarah Johnson (General): 5 appointments per patient');
        $this->command->info('  • Dr. Michael Chen (Cardiology): 8 appointments per patient');
        $this->command->info('  • Dr. Emily Rodriguez (Pediatrics): 3 appointments per patient');
        $this->command->info('');
        $this->command->info('Doctor logins: dr.johnson@clinic.com, dr.chen@clinic.com, dr.rodriguez@clinic.com');
        $this->command->info('Patient logins: john.smith@email.com, mary.johnson@email.com, etc.');
        $this->command->info('Default password for all users: password');
    }
}