<?php

namespace App\Services;

use App\Models\Bill;
use App\Repositories\Eloquent\BillRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialAnalyticsService
{
    protected $billRepository;
    
    public function __construct(BillRepository $billRepository)
    {
        $this->billRepository = $billRepository;
    }
    
    public function getRevenueAnalytics(string $timeframe = 'monthly', ?string $fromDate = null, ?string $toDate = null): array
    {
        // Normalize timeframe parameter
        $timeframeMap = [
            'day' => 'daily',
            'week' => 'weekly',
            'month' => 'monthly',
            'quarter' => 'monthly', // Handle quarter as monthly for now
            'year' => 'yearly'
        ];
        
        $normalizedTimeframe = $timeframeMap[$timeframe] ?? $timeframe;
        
        // Date handling logic
        $toDate = $toDate ? Carbon::parse($toDate) : Carbon::now();
        $fromDate = $fromDate ? Carbon::parse($fromDate) : $toDate->copy()->subMonths(6);
        
        // Get current and previous period dates
        $currentPeriodStart = $fromDate->copy();
        $currentPeriodEnd = $toDate->copy();
        $periodDuration = $currentPeriodEnd->diffInDays($currentPeriodStart);
        $previousPeriodEnd = $currentPeriodStart->copy()->subDay();
        $previousPeriodStart = $previousPeriodEnd->copy()->subDays($periodDuration);
        
        // Get bills data
        $bills = $this->billRepository->getBillsByDateRange($fromDate, $toDate);
        $previousBills = $this->billRepository->getBillsByDateRange($previousPeriodStart, $previousPeriodEnd);
        
        // Calculate metrics
        $totalRevenue = $bills->sum('amount');
        $previousPeriodRevenue = $previousBills->sum('amount');
        $allTimeRevenue = $this->billRepository->getTotalRevenue();
        
        // Calculate growth rate
        $growthRate = $this->calculateGrowthRate($totalRevenue, $previousPeriodRevenue);
        
        // Calculate other metrics
        $averageBillAmount = $this->calculateAverageBillAmount($bills);
        
        // Generate period data
        $revenueByPeriod = $this->generateRevenueByPeriod($bills, $fromDate, $toDate, $normalizedTimeframe);
        
        return [
            'revenue_metrics' => [
                'total_revenue' => $allTimeRevenue,
                'period_revenue' => $totalRevenue,
                'previous_period_revenue' => $previousPeriodRevenue,
                'growth_rate' => round($growthRate, 2),
                'average_bill_amount' => round($averageBillAmount, 2),
                'bill_count' => $bills->count(),
            ],
            'revenue_by_period' => $revenueByPeriod
        ];
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
}