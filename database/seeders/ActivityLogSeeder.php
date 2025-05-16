<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\ActivityLogger;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the activity log seeder.
     */
    public function run()
    {
        $users = User::all(); // Use the imported User class
        $startDate = now()->subDays(30);
        
        foreach ($users as $user) {
            // Generate 3-5 logins per user
            $loginCount = rand(3, 5);
            
            for ($i = 0; $i < $loginCount; $i++) {
                $loginDate = $startDate->copy()->addDays(rand(0, 29));
                
                // Create login activity
                app(ActivityLogger::class)
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => '127.0.0.' . rand(1, 255),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
                    ])
                    ->log('login');
                
                // Set timestamp manually
                DB::table('activity_log')
                    ->where('log_name', 'default')
                    ->where('causer_id', $user->id)
                    ->orderBy('id', 'desc')
                    ->limit(1)
                    ->update([
                        'created_at' => $loginDate->format('Y-m-d H:i:s'),
                        'updated_at' => $loginDate->format('Y-m-d H:i:s')
                    ]);
                
                // Most logins should have corresponding logouts
                if (rand(1, 10) <= 8) {
                    $logoutTime = $loginDate->copy()->addHours(rand(1, 8));
                    
                    // Create logout activity
                    app(ActivityLogger::class)
                        ->causedBy($user)
                        ->withProperties([
                            'ip' => '127.0.0.' . rand(1, 255),
                            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
                        ])
                        ->log('logout');
                    
                    // Set timestamp manually
                    DB::table('activity_log')
                        ->where('log_name', 'default')
                        ->where('causer_id', $user->id)
                        ->orderBy('id', 'desc')
                        ->limit(1)
                        ->update([
                            'created_at' => $logoutTime->format('Y-m-d H:i:s'),
                            'updated_at' => $logoutTime->format('Y-m-d H:i:s')
                        ]);
                }
            }
        }
    }
}