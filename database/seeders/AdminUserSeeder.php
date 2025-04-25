<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
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

        // Create a test patient
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
    }
}
