<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Type\Time;

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
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
            AiModelSeeder::class,
            ActivityLogSeeder::class,
            BillSeeder::class,
            TimeSlotSeeder::class,  
            AppointmentSeeder::class,
        ]);
    }
}
