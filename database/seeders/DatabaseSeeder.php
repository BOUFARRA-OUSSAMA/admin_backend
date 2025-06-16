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
            // ✅ FIXED ORDER: Base seeders first
            RoleSeeder::class,
            
            // ✅ Create all permissions first (including appointment permissions)
            PermissionSeeder::class,  // This now handles ALL permissions and admin assignment
            
            // ✅ This will ensure admin gets any additional permissions
            AppointmentPermissionSeeder::class,
            
            // Then create users
            AdminUserSeeder::class,
            
            // Users and profiles (doctors/patients)
            AppointmentTestUsersSeeder::class,
            
            // Patient seeder BEFORE bill seeder
            PatientSeeder::class,
            DoctorSpecialtySeeder::class,
            
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
