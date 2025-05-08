<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;

class AnalyticsController extends Controller
{
    /**
     * Get login failure statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoginFailures(Request $request)
    {
        $timeframe = $request->query('timeframe', 'week'); // Default to past week
        $from = null;
        $to = now();
        
        // Set time range based on requested timeframe
        switch ($timeframe) {
            case 'day':
                $from = now()->subDay();
                break;
            case 'week':
                $from = now()->subWeek();
                break;
            case 'month':
                $from = now()->subMonth();
                break;
            case 'year':
                $from = now()->subYear();
                break;
            default:
                $from = now()->subWeek();
        }
        
        // Get failed login attempts
        $failedLogins = ActivityLog::where('action', 'failed_login')
            ->whereBetween('created_at', [$from, $to])
            ->get();
        
        // Group by date
        $byDate = $failedLogins->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        })->map->count();
        
        // Group by IP address
        $byIp = $failedLogins->groupBy('ip_address')->map->count()->sortDesc()->take(10);
        
        // Group by user (if user exists)
        $byUser = $failedLogins->whereNotNull('user_id')
            ->groupBy('user_id')
            ->map(function ($group) {
                $user = User::find($group->first()->user_id);
                $email = $user ? $user->email : 'Unknown';
                return [
                    'count' => $group->count(),
                    'email' => $email,
                    'last_attempt' => $group->sortByDesc('created_at')->first()->created_at
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_failed_attempts' => $failedLogins->count(),
                'by_date' => $byDate,
                'by_ip' => $byIp,
                'by_user' => $byUser
            ]
        ]);
    }
}
