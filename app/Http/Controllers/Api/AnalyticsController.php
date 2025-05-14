<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Role;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get user statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserStats(Request $request)
    {
        $timeframe = $request->query('timeframe', 'month');
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
                $from = now()->subMonth();
        }
        
        // Get users created in the timeframe
        $newUsers = User::whereBetween('created_at', [$from, $to])->count();
        
        // Get total users
        $totalUsers = User::count();
        
        // Get users by status
        $byStatus = User::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Ensure all statuses are present in the response
        $allStatuses = ['active', 'pending', 'inactive'];
        foreach ($allStatuses as $status) {
            if (!isset($byStatus[$status])) {
                $byStatus[$status] = 0;
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'new_users' => $newUsers,
                'by_status' => $byStatus,
                'timeframe' => $timeframe
            ]
        ]);
    }
    
    /**
     * Get role statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoleStats(Request $request)
    {
        // Get all roles with user counts
        $roles = Role::withCount('users')
            ->orderBy('users_count', 'desc')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'code' => $role->code,
                    'users_count' => $role->users_count,
                    'permissions_count' => $role->permissions()->count()
                ];
            });
        
        // Calculate total assigned roles (may be more than user count due to multiple roles per user)
        $totalAssigned = $roles->sum('users_count');
        
        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'total_roles' => $roles->count(),
                'total_assigned' => $totalAssigned
            ]
        ]);
    }
    
    /**
     * Get activity statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityStats(Request $request)
    {
        $timeframe = $request->query('timeframe', 'week');
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
        
        // Get activities in the timeframe
        $activities = ActivityLog::whereBetween('created_at', [$from, $to])->get();
        
        // Group by module
        $byModule = $activities->groupBy('module')
            ->map->count()
            ->sortDesc();
        
        // Group by action
        $byAction = $activities->groupBy('action')
            ->map->count()
            ->sortDesc();
        
        // Group by date
        $byDate = $activities->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        })->map->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_activities' => $activities->count(),
                'by_module' => $byModule,
                'by_action' => $byAction,
                'by_date' => $byDate,
                'timeframe' => $timeframe
            ]
        ]);
    }
    
    /**
     * Get login statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoginStats(Request $request)
    {
        $timeframe = $request->query('timeframe', 'week');
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
        
        // Get successful logins
        $successfulLogins = ActivityLog::where('action', 'login')
            ->whereBetween('created_at', [$from, $to])
            ->get();
        
        // Group by date
        $byDate = $successfulLogins->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        })->map->count();
        
        // Get most active users
        $activeUsers = $successfulLogins
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->map(function ($group) {
                $user = User::find($group->first()->user_id);
                $email = $user ? $user->email : 'Unknown';
                return [
                    'count' => $group->count(),
                    'email' => $email,
                    'last_login' => $group->sortByDesc('created_at')->first()->created_at
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_logins' => $successfulLogins->count(),
                'by_date' => $byDate,
                'active_users' => $activeUsers,
                'timeframe' => $timeframe
            ]
        ]);
    }

    /**
     * Get login failure statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoginFailures(Request $request)
    {
        $timeframe = $request->query('timeframe', 'week');
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
    
    /**
     * Export analytics data as CSV.
     *
     * @param Request $request
     * @param string $type
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function exportData(Request $request, $type)
    {
        $allowedTypes = ['users', 'activities', 'logins', 'login-failures'];
        
        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid export type',
                'errors' => ['type' => 'Allowed types are: ' . implode(', ', $allowedTypes)]
            ], 400);
        }
        
        $timeframe = $request->query('timeframe', 'month');
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
                $from = now()->subMonth();
        }
        
        // Generate file name
        $fileName = $type . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        // Generate CSV based on type
        return response()->stream(function () use ($type, $from, $to) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            switch ($type) {
                case 'users':
                    fputcsv($file, ['ID', 'Name', 'Email', 'Status', 'Created At']);
                    $users = User::whereBetween('created_at', [$from, $to])->get();
                    foreach ($users as $user) {
                        fputcsv($file, [$user->id, $user->name, $user->email, $user->status, $user->created_at]);
                    }
                    break;
                    
                case 'activities':
                    fputcsv($file, ['ID', 'User', 'Action', 'Module', 'Description', 'Created At']);
                    $activities = ActivityLog::with('user')
                        ->whereBetween('created_at', [$from, $to])
                        ->get();
                    foreach ($activities as $activity) {
                        $userName = $activity->user ? $activity->user->name : 'System';
                        fputcsv($file, [
                            $activity->id,
                            $userName,
                            $activity->action,
                            $activity->module,
                            $activity->description,
                            $activity->created_at
                        ]);
                    }
                    break;
                    
                case 'logins':
                    fputcsv($file, ['ID', 'User', 'IP Address', 'User Agent', 'Created At']);
                    $logins = ActivityLog::with('user')
                        ->where('action', 'login')
                        ->whereBetween('created_at', [$from, $to])
                        ->get();
                    foreach ($logins as $login) {
                        $userName = $login->user ? $login->user->name : 'Unknown';
                        fputcsv($file, [
                            $login->id,
                            $userName,
                            $login->ip_address,
                            substr($login->user_agent, 0, 100), // Truncate long user agents
                            $login->created_at
                        ]);
                    }
                    break;
                    
                case 'login-failures':
                    fputcsv($file, ['ID', 'Email Attempt', 'IP Address', 'User Agent', 'Created At']);
                    $failures = ActivityLog::where('action', 'failed_login')
                        ->whereBetween('created_at', [$from, $to])
                        ->get();
                    foreach ($failures as $failure) {
                        // Extract email from old_values if available
                        $emailAttempt = 'Unknown';
                        if ($failure->old_values && isset($failure->old_values['email'])) {
                            $emailAttempt = $failure->old_values['email'];
                        }
                        
                        fputcsv($file, [
                            $failure->id,
                            $emailAttempt,
                            $failure->ip_address,
                            substr($failure->user_agent, 0, 100), // Truncate long user agents
                            $failure->created_at
                        ]);
                    }
                    break;
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
    
    /**
     * Get user registration trends.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserRegistrations(Request $request)
    {
        // Get timeframe from query params (7d, 30d, 90d) with default 30d
        $timeframe = $request->query('timeframe', '30d');
        
        // Parse timeframe to determine start date
        $days = 30; // Default
        switch ($timeframe) {
            case '7d':
                $days = 7;
                break;
            case '30d':
                $days = 30;
                break;
            case '90d':
                $days = 90;
                break;
        }
        
        $from = now()->subDays($days)->startOfDay();
        $to = now()->endOfDay();
        
        // Get user registrations within the time range
        $users = User::whereBetween('created_at', [$from, $to])->get();
        
        // Group registrations by date
        $byDate = $users->groupBy(function ($user) {
            return $user->created_at->format('Y-m-d');
        })->map->count();
        
        // Ensure we have entries for all dates in the range, even if count is 0
        $allDates = [];
        $allCounts = [];
        $currentDate = clone $from;
        
        while ($currentDate <= $to) {
            $dateString = $currentDate->format('Y-m-d');
            $allDates[] = $dateString;
            $allCounts[] = $byDate[$dateString] ?? 0;
            $currentDate->addDay();
        }
        
        // Calculate total registrations
        $totalRegistrations = array_sum($allCounts);
        
        // Calculate average daily registrations
        $averageDaily = $totalRegistrations > 0 ? round($totalRegistrations / count($allDates), 1) : 0;
        
        // Calculate growth rate by comparing first half to second half
        $halfIndex = floor(count($allCounts) / 2);
        $firstHalf = array_sum(array_slice($allCounts, 0, $halfIndex));
        $secondHalf = array_sum(array_slice($allCounts, $halfIndex));
        
        $growthRate = 0;
        if ($firstHalf > 0) {
            $growthRate = round((($secondHalf - $firstHalf) / $firstHalf) * 100);
        }
        
        // Find peak day (day with most registrations)
        $maxCount = 0;
        $peakDay = $allDates[0] ?? null;
        
        foreach ($allDates as $index => $date) {
            if ($allCounts[$index] > $maxCount) {
                $maxCount = $allCounts[$index];
                $peakDay = $date;
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'dates' => $allDates,
                'counts' => $allCounts,
                'metrics' => [
                    'total_registrations' => $totalRegistrations,
                    'growth_rate' => $growthRate,
                    'average_daily' => $averageDaily,
                    'peak_day' => $peakDay
                ],
                'timeframe' => $timeframe
            ]
        ]);
    }
}
