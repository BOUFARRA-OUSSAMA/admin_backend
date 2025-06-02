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
            
            // Appointment-related data (NO TimeSlotSeeder)
            // ✅ REMOVED: TimeSlotSeeder::class,
            AppointmentSeeder::class,
            BlockedTimeSlotsSeeder::class,
            
            // Other seeders
            AiModelSeeder::class,
            ActivityLogSeeder::class,
            BillSeeder::class,
        ]);
    }
}
