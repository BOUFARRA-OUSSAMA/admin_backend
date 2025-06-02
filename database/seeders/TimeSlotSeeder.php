<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating time slots...');
        
        // Créer des créneaux horaires standard pour les rendez-vous
        $timeSlots = [
            ['start_time' => '08:00', 'end_time' => '08:30'],
            ['start_time' => '08:30', 'end_time' => '09:00'],
            ['start_time' => '09:00', 'end_time' => '09:30'],
            ['start_time' => '09:30', 'end_time' => '10:00'],
            ['start_time' => '10:00', 'end_time' => '10:30'],
            ['start_time' => '10:30', 'end_time' => '11:00'],
            ['start_time' => '11:00', 'end_time' => '11:30'],
            ['start_time' => '11:30', 'end_time' => '12:00'],
            ['start_time' => '14:00', 'end_time' => '14:30'],
            ['start_time' => '14:30', 'end_time' => '15:00'],
            ['start_time' => '15:00', 'end_time' => '15:30'],
            ['start_time' => '15:30', 'end_time' => '16:00'],
            ['start_time' => '16:00', 'end_time' => '16:30'],
            ['start_time' => '16:30', 'end_time' => '17:00'],
        ];
        
        foreach ($timeSlots as $slot) {
            TimeSlot::updateOrCreate(
                [
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time']
                ],
                [
                    'is_active' => true
                ]
            );
        }
        
        $this->command->info('Created ' . count($timeSlots) . ' time slots.');
    }
}