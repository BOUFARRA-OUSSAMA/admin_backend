<?php


namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class AnalyticsService
{
    /**
     * Calculate time range based on standard timeframe parameter.
     *
     * @param string $timeframe day|week|month|year
     * @return array [$from, $to]
     */
    public function calculateTimeRange(string $timeframe = 'month'): array
    {
        $to = now();
        
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
        
        return [$from, $to];
    }

    /**
     * Calculate time range based on day format timeframe (7d, 30d, 90d).
     *
     * @param string $timeframe
     * @return array [$startDate, $endDate, $days]
     */
    public function calculateDayTimeRange(string $timeframe = '30d'): array
    {
        $allowed = ['7d', '30d', '90d'];
        
        if (!in_array($timeframe, $allowed)) {
            $timeframe = '30d';
        }
        
        $days = (int) str_replace('d', '', $timeframe);
        $endDate = now();
        $startDate = now()->subDays($days);
        
        return [$startDate, $endDate, $days];
    }
    
    /**
     * Fetch activity data for a specific action.
     *
     * @param string $action
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getActivityData(string $action, Carbon $startDate, Carbon $endDate): array
    {
        return ActivityLog::where('action', $action)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
    }
    
    /**
     * Calculate active sessions data.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function calculateActiveSessions(Carbon $startDate, Carbon $endDate): array
    {
        // Get session data from sessions table - with PostgreSQL compatible timestamp conversion
        $sessionDataFromTable = DB::table('sessions')
            ->where('last_activity', '>=', $startDate->timestamp)
            ->where('last_activity', '<=', $endDate->timestamp)
            ->selectRaw('DATE(to_timestamp(last_activity)) as date, COUNT(DISTINCT user_id) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Calculate sessions from login/logout events
        $sessionDataFromEvents = [];
        $loginsByUserAndDate = ActivityLog::where('action', 'login')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->get()
            ->groupBy(function ($log) {
                return $log->user_id . '_' . $log->created_at->format('Y-m-d');
            });

        foreach ($loginsByUserAndDate as $key => $logs) {
            list($userId, $date) = explode('_', $key);
            if (!isset($sessionDataFromEvents[$date])) {
                $sessionDataFromEvents[$date] = 0;
            }
            $sessionDataFromEvents[$date]++;
        }

        // Merge both data sources, preferring the sessions table data where available
        $activeSessions = $sessionDataFromTable;
        foreach ($sessionDataFromEvents as $date => $count) {
            if (!isset($activeSessions[$date])) {
                $activeSessions[$date] = $count;
            } else {
                // Take the higher value between the two sources
                $activeSessions[$date] = max($activeSessions[$date], $count);
            }
        }
        
        return $activeSessions;
    }

    /**
     * Generate daily data for activity charts.
     *
     * @param Carbon $startDate
     * @param int $days
     * @param array $loginData
     * @param array $activeSessions
     * @param array $logoutData
     * @return array
     */
    public function generateDailyData(Carbon $startDate, int $days, array $loginData, array $activeSessions, array $logoutData): array
    {
        $dailyData = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $dailyData[] = [
                'date' => $date,
                'logins' => $loginData[$date] ?? 0,
                'active_sessions' => $activeSessions[$date] ?? 0,
                'logouts' => $logoutData[$date] ?? 0,
            ];
        }
        return $dailyData;
    }

    /**
     * Calculate activity summary from daily data.
     *
     * @param array $dailyData
     * @return array
     */
    public function calculateActivitySummary(array $dailyData): array
    {
        // Calculate total logins
        $totalLogins = array_sum(array_column($dailyData, 'logins'));
        
        // Calculate average sessions
        $averageSessions = count($dailyData) > 0 
            ? array_sum(array_column($dailyData, 'active_sessions')) / count($dailyData) 
            : 0;
        
        // Find peak day
        $peakIndex = 0;
        $peakValue = 0;
        foreach ($dailyData as $index => $day) {
            if ($day['logins'] > $peakValue) {
                $peakValue = $day['logins'];
                $peakIndex = $index;
            }
        }
        
        $peakDay = [];
        if (isset($dailyData[$peakIndex])) {
            $peakDate = $dailyData[$peakIndex]['date'];
            $peakDay = [
                'date' => $peakDate,
                'day' => Carbon::parse($peakDate)->format('l'),
                'logins' => $peakValue
            ];
        }
        
        return [
            'total_logins' => $totalLogins,
            'average_sessions' => round($averageSessions),
            'peak_day' => $peakDay
        ];
    }

    /**
     * Generate date range array with counts.
     *
     * @param Carbon $from
     * @param Carbon $to
     * @param array $countsByDate
     * @return array
     */
    public function generateDateRange(Carbon $from, Carbon $to, array $countsByDate): array
    {
        $allDates = [];
        $allCounts = [];
        $currentDate = clone $from;
        
        while ($currentDate <= $to) {
            $dateString = $currentDate->format('Y-m-d');
            $allDates[] = $dateString;
            $allCounts[] = $countsByDate[$dateString] ?? 0;
            $currentDate->addDay();
        }
        
        return [$allDates, $allCounts];
    }
}