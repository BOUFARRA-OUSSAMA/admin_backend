<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Role;
use App\Services\AnalyticsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Services\FinancialAnalyticsService; 
use App\Http\Requests\Analytics\RevenueAnalyticsRequest; 
use App\Http\Requests\Analytics\DateRangeRequest;
use App\Services\DateFilterService;

class AnalyticsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var AnalyticsService
     */
    protected $analyticsService;

    /**
     * AnalyticsController constructor.
     *
     * @param AnalyticsService $analyticsService
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get user statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserStats(Request $request)
    {
        $timeframe = $request->query('timeframe', 'month');
        [$from, $to] = $this->analyticsService->calculateTimeRange($timeframe);
        
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
        [$from, $to] = $this->analyticsService->calculateTimeRange($timeframe);
        
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
        [$from, $to] = $this->analyticsService->calculateTimeRange($timeframe);
        
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
     * Export analytics data as CSV.
     *
     * @param Request $request
     * @param string $type
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function exportData(Request $request, $type)
    {
        $allowedTypes = ['users', 'activities', 'logins'];
        
        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid export type',
                'errors' => ['type' => 'Allowed types are: ' . implode(', ', $allowedTypes)]
            ], 400);
        }
        
        $timeframe = $request->query('timeframe', 'month');
        [$from, $to] = $this->analyticsService->calculateTimeRange($timeframe);
        
        // Generate file name
        $fileName = $type . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        // Generate CSV based on type
        return response()->stream(function () use ($type, $from, $to) {
            $file = fopen('php://output', 'w');
            
            // Add headers and data based on type
            switch ($type) {
                case 'users':
                    $this->exportUsers($file, $from, $to);
                    break;
                    
                case 'activities':
                    $this->exportActivities($file, $from, $to);
                    break;
                    
                case 'logins':
                    $this->exportLogins($file, $from, $to);
                    break;
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
    
    /**
     * Export users data to CSV.
     *
     * @param resource $file
     * @param Carbon $from
     * @param Carbon $to
     * @return void
     */
    private function exportUsers($file, Carbon $from, Carbon $to): void
    {
        fputcsv($file, ['ID', 'Name', 'Email', 'Status', 'Created At']);
        $users = User::whereBetween('created_at', [$from, $to])->get();
        foreach ($users as $user) {
            fputcsv($file, [$user->id, $user->name, $user->email, $user->status, $user->created_at]);
        }
    }
    
    /**
     * Export activities data to CSV.
     *
     * @param resource $file
     * @param Carbon $from
     * @param Carbon $to
     * @return void
     */
    private function exportActivities($file, Carbon $from, Carbon $to): void
    {
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
    }
    
    /**
     * Export logins data to CSV.
     *
     * @param resource $file
     * @param Carbon $from
     * @param Carbon $to
     * @return void
     */
    private function exportLogins($file, Carbon $from, Carbon $to): void
    {
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
        [$startDate, $endDate, $days] = $this->analyticsService->calculateDayTimeRange($timeframe);
        
        // Get user registrations within the time range
        $users = User::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Group registrations by date
        $byDate = $users->groupBy(function ($user) {
            return $user->created_at->format('Y-m-d');
        })->map->count();
        
        // Convert the Collection to an array before passing to generateDateRange
        [$allDates, $allCounts] = $this->analyticsService->generateDateRange($startDate, $endDate, $byDate->toArray());
        
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

    /**
     * Get user activity statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserActivityStats(Request $request)
    {
        // Validate timeframe
        $timeframe = $request->input('timeframe', '30d');
        [$startDate, $endDate, $days] = $this->analyticsService->calculateDayTimeRange($timeframe);

        // Fetch login and logout data
        $loginData = $this->analyticsService->getActivityData('login', $startDate, $endDate);
        $logoutData = $this->analyticsService->getActivityData('logout', $startDate, $endDate);

        // Calculate active sessions
        $activeSessions = $this->analyticsService->calculateActiveSessions($startDate, $endDate);

        // Generate daily data
        $dailyData = $this->analyticsService->generateDailyData($startDate, $days, $loginData, $activeSessions, $logoutData);
        
        // Calculate summary
        $summary = $this->analyticsService->calculateActivitySummary($dailyData);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'daily_data' => $dailyData,
                'timeframe' => $timeframe
            ]
        ]);
    }

    /**
     * Get current active user sessions.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentActiveSessions(Request $request)
    {
        $cacheKey = 'active_sessions';
        $cacheTtl = 30; // seconds

        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);
        } else {
            $data = $this->analyticsService->getCurrentActiveSessions();
            Cache::put($cacheKey, $data, $cacheTtl);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get revenue analytics with specified timeframe and filters
     * 
     * @param RevenueAnalyticsRequest $request
     * @param FinancialAnalyticsService $service
     * @param DateFilterService $dateFilterService
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRevenueAnalytics(
        RevenueAnalyticsRequest $request, 
        FinancialAnalyticsService $service,
        DateFilterService $dateFilterService
    ) {
        // Start with the original parameters
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $year = $request->input('year');
        $timeframe = $request->input('timeframe') ?? 'monthly';
        
        // Handle preset_period if provided
        if ($request->has('preset_period')) {
            $dateRange = $dateFilterService->getDateRangeFromPreset(
                $request->input('preset_period'), 
                $request->header('Timezone')
            );
            $fromDate = $dateRange['date_from'];
            $toDate = $dateRange['date_to'];
        }
        // Handle year-specific filtering (keeping your existing logic)
        else if ($timeframe === 'yearly' && $year) {
            $fromDate = Carbon::createFromDate($year, 1, 1)->format('Y-m-d');
            $toDate = Carbon::createFromDate($year, 12, 31)->format('Y-m-d');
        }
        
        // Collect additional filters
        $additionalFilters = $request->only([
            'doctor_id',
            'doctor_name',
            'patient_id',
            'payment_method',
            'service_type'
        ]);
        
        // Get revenue analytics using the determined date range and additional filters
        $data = $service->getRevenueAnalytics(
            $timeframe,
            $fromDate,
            $toDate,
            $additionalFilters
        );
        
        // Keep your original response structure
        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $data['revenue_metrics']['total_revenue'],
                'period_revenue' => $data['revenue_metrics']['period_revenue'],
                'growth_rate' => $data['revenue_metrics']['growth_rate'],
                'average_bill_amount' => $data['revenue_metrics']['average_bill_amount'],
                'bill_count' => $data['revenue_metrics']['bill_count'],
                'revenue_by_period' => $data['revenue_by_period']
            ]
        ]);
    }

    /**
     * Get service type analytics
     * 
     * @param DateRangeRequest $request
     * @param FinancialAnalyticsService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServiceAnalytics(DateRangeRequest $request, FinancialAnalyticsService $service)
    {
        $toDate = isset($request['to_date']) ? Carbon::parse($request['to_date']) : Carbon::now();
        $fromDate = isset($request['from_date']) ? Carbon::parse($request['from_date']) : $toDate->copy()->subMonths(6);

        // Collect additional filters (if you implement this in ServiceAnalytics method)
        $additionalFilters = $request->only([
            'doctor_id',
            'doctor_name',
            'patient_id',
            'payment_method'
        ]);

        $serviceBreakdown = $service->getServiceAnalytics($fromDate, $toDate, $additionalFilters);
        
        return response()->json([
            'success' => true,
            'data' => [
                'service_breakdown' => $serviceBreakdown
            ]
        ]);
    }

    /**
     * Get doctor revenue analytics
     * 
     * @param DateRangeRequest $request
     * @param FinancialAnalyticsService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDoctorRevenueAnalytics(DateRangeRequest $request, FinancialAnalyticsService $service)
    {
        $toDate = isset($request['to_date']) ? Carbon::parse($request['to_date']) : Carbon::now();
        $fromDate = isset($request['from_date']) ? Carbon::parse($request['from_date']) : $toDate->copy()->subMonths(6);

        $doctorRevenueData = $service->getDoctorRevenueAnalytics($fromDate, $toDate);

        return response()->json([
            'success' => true,
            'data' => [
                'doctor_revenue' => $doctorRevenueData
            ]
        ]);
    }

    /**
     * Get current week revenue analytics
     */
    public function getCurrentWeekRevenue(FinancialAnalyticsService $service)
    {
        $data = $service->getRevenueAnalytics('weekly');
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $data['revenue_metrics']['period_revenue'],
                'average_bill_amount' => $data['revenue_metrics']['average_bill_amount'],
                'bill_count' => $data['revenue_metrics']['bill_count'],
                'start_date' => Carbon::now()->startOfWeek()->format('Y-m-d'),
                'end_date' => Carbon::now()->endOfWeek()->format('Y-m-d'),
                'daily_breakdown' => $data['revenue_by_period']
            ]
        ]);
    }

    /**
     * Get current month revenue analytics
     */
    public function getCurrentMonthRevenue(FinancialAnalyticsService $service)
    {
        $data = $service->getRevenueAnalytics('monthly');
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $data['revenue_metrics']['period_revenue'],
                'average_bill_amount' => $data['revenue_metrics']['average_bill_amount'],
                'bill_count' => $data['revenue_metrics']['bill_count'],
                'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
                'daily_breakdown' => $data['revenue_by_period']
            ]
        ]);
    }

    /**
     * Get current year revenue analytics
     */
    public function getCurrentYearRevenue(FinancialAnalyticsService $service)
    {
        $data = $service->getRevenueAnalytics('yearly');
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $data['revenue_metrics']['period_revenue'],
                'average_bill_amount' => $data['revenue_metrics']['average_bill_amount'],
                'bill_count' => $data['revenue_metrics']['bill_count'],
                'start_date' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'end_date' => Carbon::now()->endOfYear()->format('Y-m-d'),
                'monthly_breakdown' => $data['revenue_by_period']
            ]
        ]);
    }
}