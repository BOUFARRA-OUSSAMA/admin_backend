<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create default roles
        $roles = [
            [
                'name' => 'Administrator',
                'code' => 'admin',
                'description' => 'Full system access'
            ],
            [
                'name' => 'Doctor',
                'code' => 'doctor',
                'description' => 'Healthcare provider with patient access'
            ],
            [
                'name' => 'Nurse',
                'code' => 'nurse',
                'description' => 'Healthcare provider with limited access'
            ],
            [
                'name' => 'Receptionist',
                'code' => 'receptionist',
                'description' => 'Front desk staff for patient management'
            ],
            [
                'name' => 'Patient',
                'code' => 'patient',
                'description' => 'Healthcare service recipient'
            ],
            [
                'name' => 'Guest',
                'code' => 'guest',
                'description' => 'Limited access for new users'
            ]
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['code' => $role['code']],
                [
                    'name' => $role['name'],
                    'description' => $role['description']
                ]
            );
        }
    }
}
