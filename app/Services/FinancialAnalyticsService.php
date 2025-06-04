<?php

namespace App\Services;

use App\Models\Bill;
use App\Repositories\Eloquent\BillRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialAnalyticsService
{
    protected $billRepository;
    
    public function __construct(BillRepository $billRepository)
    {
        $this->billRepository = $billRepository;
        
        // Ensure consistent timezone for calculations
        // Use Azure App Service timezone or default to UTC
        $timezone = env('ANALYTICS_TIMEZONE', 'UTC');
        date_default_timezone_set($timezone);
    }
    
    /**
     * Get revenue analytics with specified timeframe and optional filters
     * 
     * @param string $timeframe
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param array $additionalFilters Additional filters (doctor_id, doctor_name, etc.)
     * @return array
     */
    public function getRevenueAnalytics(
        string $timeframe = 'monthly', 
        ?string $fromDate = null, 
        ?string $toDate = null,
        array $additionalFilters = []
    ): array {
        // Normalize timeframe parameter
        $timeframeMap = [
            'day' => 'daily',
            'week' => 'weekly',
            'month' => 'monthly',
            'quarter' => 'monthly', 
            'year' => 'yearly'
        ];
        
        $normalizedTimeframe = $timeframeMap[$timeframe] ?? $timeframe;
        
        // Date handling logic - use timeframe boundaries if no dates provided
        if ($fromDate === null && $toDate === null) {
            [$fromDate, $toDate, $periodStatus] = $this->getTimeframeBoundaries($normalizedTimeframe);
        } else {
            $toDate = $toDate ? Carbon::parse($toDate) : Carbon::now();
            $fromDate = $fromDate ? Carbon::parse($fromDate) : $toDate->copy()->subMonths(6);
            $periodStatus = null;
        }
        
        // Get current and previous period dates
        $currentPeriodStart = $fromDate;
        $currentPeriodEnd = $toDate;
        $periodDuration = $currentPeriodEnd->diffInDays($currentPeriodStart);
        $previousPeriodEnd = $currentPeriodStart->copy()->subDay();
        $previousPeriodStart = $previousPeriodEnd->copy()->subDays($periodDuration);
        
        // Get bills data - only get bills up to the current date
        $effectiveEndDate = Carbon::now()->min($toDate);
        
        // Use additional filters if provided, otherwise use original method
        if (!empty($additionalFilters)) {
            // Use custom query with additional filters
            $bills = $this->getBillsWithFilters($fromDate, $effectiveEndDate, $additionalFilters);
            $previousBills = $this->getBillsWithFilters($previousPeriodStart, $previousPeriodEnd, $additionalFilters);
        } else {
            // Use original method for backward compatibility
            $bills = $this->billRepository->getBillsByDateRange($fromDate, $effectiveEndDate);
            $previousBills = $this->billRepository->getBillsByDateRange($previousPeriodStart, $previousPeriodEnd);
        }
        
        // Calculate metrics
        $totalRevenue = $bills->sum('amount');
        $previousPeriodRevenue = $previousBills->sum('amount');
        $allTimeRevenue = $this->billRepository->getTotalRevenue();
        
        // Calculate growth rate
        $growthRate = $this->calculateGrowthRate($totalRevenue, $previousPeriodRevenue);
        
        // Calculate other metrics
        $averageBillAmount = $this->calculateAverageBillAmount($bills);
        
        // Generate period data
        $revenueByPeriod = $this->generateRevenueByPeriod(
            $bills, 
            $fromDate, 
            $effectiveEndDate, 
            $normalizedTimeframe
        );
        
        $result = [
            'revenue_metrics' => [
                'total_revenue' => $allTimeRevenue,
                'period_revenue' => round($totalRevenue, 2),
                'previous_period_revenue' => round($previousPeriodRevenue, 2),
                'growth_rate' => round($growthRate, 2),
                'average_bill_amount' => round($averageBillAmount, 2),
                'bill_count' => $bills->count(),
            ],
            'revenue_by_period' => $revenueByPeriod,
            'date_range' => [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d'),
                'current_date' => Carbon::now()->format('Y-m-d')
            ]
        ];
        
        // Add period status if this is a current period query
        if ($periodStatus) {
            // Project full period revenue if incomplete
            $projectedRevenue = $totalRevenue;
            if (!$periodStatus['is_complete'] && $periodStatus['percent_complete'] > 0) {
                $projectedRevenue = ($totalRevenue / $periodStatus['percent_complete']) * 100;
            }
            
            $result['period_status'] = $periodStatus;
            $result['revenue_metrics']['projected_full_period_revenue'] = round($projectedRevenue, 2);
        }
        
        return $result;
    }
    
    /**
     * Get service type analytics data
     * 
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @return Collection
     */
    public function getServiceAnalytics(Carbon $fromDate, Carbon $toDate): Collection
    {
        // Get service analytics data
        $servicesData = $this->billRepository->getServiceAnalytics($fromDate, $toDate);
        
        // Format the response - keep average_price as requested
        return $servicesData->map(function ($item) {
            return [
                'service_type' => $item->service_type,
                'count' => $item->count,
                'total_revenue' => round($item->total_revenue, 2),
                'average_price' => round($item->average_price, 2) // Add this back
            ];
        });
    }

    /**
     * Get doctor revenue analytics data
     * 
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @return Collection
     */
    public function getDoctorRevenueAnalytics(Carbon $fromDate, Carbon $toDate): Collection
    {
        // Get doctor revenue data
        $doctorRevenueData = $this->billRepository->getDoctorRevenueAnalytics($fromDate, $toDate);
        
        // Calculate average bill amount and format the response
        return $doctorRevenueData->map(function ($item) {
            $averageBillAmount = $item->bill_count > 0 ? $item->total_revenue / $item->bill_count : 0;
            
            return [
                'doctor_id' => $item->doctor_id,
                'doctor_name' => $item->doctor_name,
                'total_revenue' => round($item->total_revenue, 2),
                'bill_count' => $item->bill_count,
                'average_bill_amount' => round($averageBillAmount, 2)
            ];
        });
    }
    
    /**
     * Calculate growth rate between current and previous period
     * 
     * @param float $currentValue
     * @param float $previousValue
     * @return float
     */
    private function calculateGrowthRate($currentValue, $previousValue): float
    {
        if ($previousValue <= 0) {
            return 0;
        }
        
        return (($currentValue - $previousValue) / $previousValue) * 100;
    }
    
    /**
     * Calculate average bill amount
     * 
     * @param Collection $bills
     * @return float
     */
    private function calculateAverageBillAmount(Collection $bills): float
    {
        return $bills->count() > 0 ? $bills->sum('amount') / $bills->count() : 0;
    }
    
    /**
     * Generate revenue breakdown by time period
     * 
     * @param Collection $bills
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param string $timeframe
     * @return array
     */
    private function generateRevenueByPeriod(Collection $bills, Carbon $fromDate, Carbon $toDate, string $timeframe): array
    {
        // Time formatting logic
        $format = $this->getTimeFormat($timeframe);
        $interval = $this->getTimeInterval($timeframe);
        
        // Create periods
        $periods = [];
        $currentDate = $fromDate->copy();
        
        while ($currentDate->lte($toDate)) {
            $periods[$currentDate->format($format)] = 0;
            $currentDate->add(1, $interval);
        }
        
        // Fill in revenue data
        foreach ($bills as $bill) {
            $period = Carbon::parse($bill->issue_date)->format($format);
            if (isset($periods[$period])) {
                $periods[$period] += $bill->amount;
            }
        }
        
        // Convert to array format
        $result = [];
        foreach ($periods as $period => $amount) {
            $result[] = [
                'period' => $period,
                'amount' => $amount
            ];
        }
        
        return $result;
    }
    
    /**
     * Get date format based on timeframe
     * 
     * @param string $timeframe
     * @return string
     */
    private function getTimeFormat(string $timeframe): string
    {
        switch ($timeframe) {
            case 'daily': return 'Y-m-d';
            case 'weekly': return 'Y-W';
            case 'yearly': return 'Y';
            case 'monthly':
            default: return 'Y-m';
        }
    }
    
    /**
     * Get time interval based on timeframe
     * 
     * @param string $timeframe
     * @return string
     */
    private function getTimeInterval(string $timeframe): string
    {
        switch ($timeframe) {
            case 'daily': return 'day';
            case 'weekly': return 'week';
            case 'yearly': return 'year';
            case 'monthly':
            default: return 'month';
        }
    }

    /**
     * Get the start and end dates for the specified timeframe
     * Also returns information about the period's completion status
     */
    public function getTimeframeBoundaries(string $timeframe, ?Carbon $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?? Carbon::now();
        $start = null;
        $end = null;
        $isComplete = false;
        $currentDayInPeriod = 0;
        $totalDaysInPeriod = 0;
        $percentComplete = 0;

        switch ($timeframe) {
            case 'weekly':
                // Monday to Sunday week
                $start = $referenceDate->copy()->startOfWeek();
                $end = $referenceDate->copy()->endOfWeek();
                $isComplete = $referenceDate->isAfter($end);
                $currentDayInPeriod = $referenceDate->diffInDays($start) + 1;
                $totalDaysInPeriod = 7; // 7 days in a week
                break;
                
            case 'monthly':
                $start = $referenceDate->copy()->startOfMonth();
                $end = $referenceDate->copy()->endOfMonth();
                $isComplete = $referenceDate->isAfter($end);
                $currentDayInPeriod = $referenceDate->day;
                $totalDaysInPeriod = $end->day;
                break;
                
            case 'yearly':
                $start = $referenceDate->copy()->startOfYear();
                $end = $referenceDate->copy()->endOfYear();
                $isComplete = $referenceDate->isAfter($end);
                $currentDayInPeriod = $referenceDate->dayOfYear;
                $totalDaysInPeriod = $end->dayOfYear;
                break;
                
            case 'daily':
            default:
                $start = $referenceDate->copy()->startOfDay();
                $end = $referenceDate->copy()->endOfDay();
                $isComplete = $referenceDate->isAfter($end);
                $currentDayInPeriod = 1;
                $totalDaysInPeriod = 1;
        }

        // Calculate percentage complete for the period
        $percentComplete = min(100, round(($currentDayInPeriod / $totalDaysInPeriod) * 100, 1));

        return [
            $start, 
            $end, 
            [
                'is_complete' => $isComplete,
                'current_day' => $currentDayInPeriod,
                'total_days' => $totalDaysInPeriod,
                'percent_complete' => $percentComplete
            ]
        ];
    }

    /**
     * Get current period revenue analytics with completion status
     * 
     * @param string $timeframe daily|weekly|monthly|yearly
     * @return array
     */
    public function getCurrentPeriodRevenue(string $timeframe = 'weekly'): array
    {
        // Start performance measurement
        $startTime = microtime(true);
        
        // Check cache first
        $cachedData = $this->getCachedCurrentPeriodRevenue($timeframe);
        if ($cachedData !== null) {
            // Log cache hit for performance tracking
            $duration = microtime(true) - $startTime;
            Log::info("Financial analytics retrieved from cache", [
                'timeframe' => $timeframe,
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            return $cachedData;
        }
        
        // Get current period boundaries and status
        [$startDate, $endDate, $periodStatus] = $this->getTimeframeBoundaries($timeframe);
        
        // Get bills for the current period (partial or complete)
        $bills = $this->billRepository->getBillsByDateRange($startDate, Carbon::now());
        
        // Calculate metrics
        $totalRevenue = $bills->sum('amount');
        $billCount = $bills->count();
        $averageBillAmount = $this->calculateAverageBillAmount($bills);
        
        // Generate daily breakdown
        $dailyBreakdown = $this->generateRevenueByPeriod(
            $bills, 
            $startDate, 
            Carbon::now()->min($endDate), 
            'daily'
        );
        
        // Project full period revenue if incomplete
        $projectedRevenue = $totalRevenue;
        if (!$periodStatus['is_complete'] && $periodStatus['percent_complete'] > 0) {
            $projectedRevenue = ($totalRevenue / $periodStatus['percent_complete']) * 100;
        }
        
        $result = [
            'revenue_metrics' => [
                'total_revenue' => round($totalRevenue, 2),
                'average_bill_amount' => round($averageBillAmount, 2),
                'bill_count' => $billCount,
                'projected_revenue' => round($projectedRevenue, 2),
            ],
            'period_info' => [
                'timeframe' => $timeframe,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'current_date' => Carbon::now()->format('Y-m-d'),
                'is_complete' => $periodStatus['is_complete'],
                'current_day' => $periodStatus['current_day'],
                'total_days' => $periodStatus['total_days'],
                'percent_complete' => $periodStatus['percent_complete']
            ],
            'daily_breakdown' => $dailyBreakdown
        ];
        
        // Cache the result
        $this->cacheCurrentPeriodRevenue($timeframe, $result);
        
        // End performance measurement and log
        $duration = microtime(true) - $startTime;
        Log::info("Financial analytics calculation completed", [
            'timeframe' => $timeframe,
            'duration_ms' => round($duration * 1000, 2),
            'bill_count' => $billCount
        ]);
        
        return $result;
    }
    
    protected function getCachedCurrentPeriodRevenue(string $timeframe): ?array
    {
        $cacheKey = "financial_analytics:current_{$timeframe}_" . Carbon::now()->format('Y-m-d');
        
        // Check if we have today's data cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        return null;
    }

    protected function cacheCurrentPeriodRevenue(string $timeframe, array $data): void
    {
        $cacheKey = "financial_analytics:current_{$timeframe}_" . Carbon::now()->format('Y-m-d');
        
        // Cache for a reasonable period based on timeframe
        $cacheDuration = match($timeframe) {
            'daily' => 1, // 1 hour for daily
            'weekly' => 2, // 2 hours for weekly
            'yearly' => 24, // 24 hours for yearly
            default => 4, // 4 hours for monthly
        };
        
        Cache::put($cacheKey, $data, now()->addHours($cacheDuration));
    }
    
    /**
     * Fallback data in case of calculation errors
     * 
     * @param string $timeframe
     * @return array
     */
    protected function getFallbackAnalyticsData(string $timeframe): array
    {
        // Log the fallback usage
        Log::warning('Fallback analytics data used', ['timeframe' => $timeframe]);
        
        // Return empty structure with timeframe
        return [
            'revenue_metrics' => [
                'total_revenue' => 0,
                'period_revenue' => 0,
                'previous_period_revenue' => 0,
                'growth_rate' => 0,
                'average_bill_amount' => 0,
                'bill_count' => 0,
            ],
            'revenue_by_period' => [],
            'date_range' => [
                'from' => '',
                'to' => '',
                'current_date' => Carbon::now()->format('Y-m-d')
            ],
            'period_status' => [
                'is_complete' => false,
                'current_day' => 0,
                'total_days' => 0,
                'percent_complete' => 0
            ]
        ];
    }
    
    /**
     * Get bills with additional filters applied
     * 
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param array $filters
     * @return Collection
     */
    private function getBillsWithFilters(Carbon $fromDate, Carbon $toDate, array $filters): Collection
    {
        // Use getModel() method instead of accessing model property directly
        $query = $this->billRepository->getModel()->newQuery();
        
        // Apply date range filters
        $query->whereDate('issue_date', '>=', $fromDate)
              ->whereDate('issue_date', '<=', $toDate);
        
        // Doctor filtering
        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_user_id', $filters['doctor_id']);
        } elseif (!empty($filters['doctor_name'])) {
            $query->whereHas('doctor', function($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['doctor_name'] . '%');
            });
        }
        
        // Patient filtering
        if (!empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }
        
        // Payment method filtering
        if (!empty($filters['payment_method'])) {
            if (strpos($filters['payment_method'], ',') !== false) {
                $paymentMethods = explode(',', $filters['payment_method']);
                $query->whereIn('payment_method', $paymentMethods);
            } else {
                $query->where('payment_method', $filters['payment_method']);
            }
        }
        
        // Service type filtering
        if (!empty($filters['service_type'])) {
            $query->whereHas('items', function($itemsQuery) use ($filters) {
                if (strpos($filters['service_type'], ',') !== false) {
                    $serviceTypes = explode(',', $filters['service_type']);
                    $itemsQuery->whereIn('service_type', $serviceTypes);
                } else {
                    $itemsQuery->where('service_type', $filters['service_type']);
                }
            });
        }
        
        return $query->get();
    }
}