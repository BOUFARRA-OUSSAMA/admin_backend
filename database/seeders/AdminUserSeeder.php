<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Patient;
use App\Models\PersonalInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user
        $adminUser = User::updateOrCreate(
            [
                'email' => 'admin@example.com',
            ],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'phone' => '1234567890',
                'status' => 'active',
            ]
        );

        // Assign admin role
        $adminRole = Role::where('code', 'admin')->first();
        if ($adminRole) {
            $adminUser->roles()->sync([$adminRole->id]);
        }

        // Create a test doctor
        $doctorUser = User::updateOrCreate(
            [
                'email' => 'doctor@example.com',
            ],
            [
                'name' => 'Test Doctor',
                'password' => Hash::make('password'),
                'phone' => '2345678901',
                'status' => 'active',
            ]
        );

        // Assign doctor role
        $doctorRole = Role::where('code', 'doctor')->first();
        if ($doctorRole) {
            $doctorUser->roles()->sync([$doctorRole->id]);
        }

        // ✅ FIX: Create a test patient WITH proper Patient record
        $patientUser = User::updateOrCreate(
            [
                'email' => 'patient@example.com',
            ],
            [
                'name' => 'Test Patient',
                'password' => Hash::make('password'),
                'phone' => '3456789012',
                'status' => 'active',
            ]
        );

        // Assign patient role
        $patientRole = Role::where('code', 'patient')->first();
        if ($patientRole) {
            $patientUser->roles()->sync([$patientRole->id]);
        }

        // ✅ CREATE THE MISSING PATIENT RECORD
        $patient = Patient::updateOrCreate(
            ['user_id' => $patientUser->id],
            [
                'registration_date' => now(),
            ]
        );

        // ✅ CREATE PERSONAL INFO FOR THE PATIENT
        PersonalInfo::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'name' => 'Test',
                'surname' => 'Patient',
                'birthdate' => '1990-01-01',
                'gender' => 'other',
                'address' => '123 Test Street, Test City',
                'emergency_contact' => 'Emergency Contact - 555-0000',
                'marital_status' => 'single',
                'blood_type' => 'O+',
                'nationality' => 'American',
            ]
        );

        $this->command->info('✅ Created admin, doctor, and patient users with proper Patient record linkage');
        $this->command->info("Patient User ID: {$patientUser->id} → Patient ID: {$patient->id}");
    }
}
