<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlockedTimeSlot;
use App\Models\User;
use Carbon\Carbon;

class BlockedTimeSlotsSeeder extends Seeder
{
    public function run()
    {
        $doctors = User::whereHas('roles', function($query) {
            $query->where('code', 'doctor');
        })->get();

        $reasons = [
            'Personal break',
            'Conference attendance',
            'Surgery',
            'Training session',
            'Emergency leave',
            'Lunch break',
            'Administrative work'
        ];

        $blockTypes = BlockedTimeSlot::getBlockTypes();

        foreach ($doctors as $doctor) {
            $numBlockedSlots = rand(2, 5);
            
            for ($i = 0; $i < $numBlockedSlots; $i++) {
                $blockDate = Carbon::today()->addDays(rand(1, 30));
                
                if ($blockDate->isWeekend()) {
                    continue;
                }

                $startHour = rand(9, 15);
                $startMinute = [0, 30][rand(0, 1)];
                $duration = [30, 60, 90, 120][rand(0, 3)];

                $startDateTime = $blockDate->copy()->setTime($startHour, $startMinute);
                $endDateTime = $startDateTime->copy()->addMinutes($duration);

                // ✅ FIXED: Now matches model fillable fields
                BlockedTimeSlot::create([
                    'doctor_user_id' => $doctor->id,
                    'start_datetime' => $startDateTime, // ✅ Full datetime
                    'end_datetime' => $endDateTime, // ✅ Full datetime
                    'reason' => $reasons[array_rand($reasons)],
                    'block_type' => $blockTypes[array_rand($blockTypes)], // ✅ Added
                    'is_recurring' => false, // ✅ Changed name
                    'recurring_pattern' => null, // ✅ Changed name
                    'recurring_end_date' => null,
                    'created_by_user_id' => $doctor->id,
                    'notes' => null, // ✅ Added
                ]);
            }
        }

        $this->command->info('Blocked time slots created successfully!');
    }
}