<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // Base seeders first
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
            
            // Appointment permission setup
            AppointmentPermissionSeeder::class,
            
            // Users and profiles (doctors/patients)
            AppointmentTestUsersSeeder::class,
            
            // Patient seeder BEFORE bill seeder
            PatientSeeder::class,
            
            // Medical data (NEW) - Must run AFTER patients and doctors
            MedicalDataSeeder::class,
            
            // Appointment-related data
            AppointmentSeeder::class,
            BlockedTimeSlotsSeeder::class,
            
            // Notification test data
            NotificationTestDataSeeder::class,
            
            // Other seeders
            AiModelSeeder::class,
            ActivityLogSeeder::class,
            BillSeeder::class,  // Now runs AFTER PatientSeeder
        ]);
    }
}
