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
        $outstandingAmount = $this->calculateOutstandingAmount($bills);
        
        // Generate period data
        $revenueByPeriod = $this->generateRevenueByPeriod($bills, $fromDate, $toDate, $normalizedTimeframe);
        
        return [
            'revenue_metrics' => [
                'total_revenue' => $allTimeRevenue,
                'period_revenue' => $totalRevenue,
                'previous_period_revenue' => $previousPeriodRevenue,
                'growth_rate' => round($growthRate, 2),
                'average_bill_amount' => round($averageBillAmount, 2),
                'outstanding_amount' => $outstandingAmount,
            ],
            'revenue_by_period' => $revenueByPeriod
        ];
    }
    
    /**
     * Get service type analytics data
     * 
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array
     */
    public function getServiceAnalytics(?string $fromDate = null, ?string $toDate = null): array
    {
        // Set default date range if not provided
        $toDate = $toDate ? Carbon::parse($toDate) : Carbon::now();
        $fromDate = $fromDate ? Carbon::parse($fromDate) : $toDate->copy()->subMonths(6);
        
        // Get service analytics data
        $servicesData = $this->billRepository->getServiceAnalytics($fromDate, $toDate);
        
        // Format the response
        $serviceBreakdown = $servicesData->map(function ($item) {
            return [
                'service_type' => $item->service_type,
                'count' => $item->count,
                'total_revenue' => round($item->total_revenue, 2),
                'average_price' => round($item->average_price, 2)
            ];
        });
        
        return [
            'service_breakdown' => $serviceBreakdown
        ];
    }

    /**
     * Get doctor revenue analytics data
     * 
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array
     */
    public function getDoctorRevenueAnalytics(?string $fromDate = null, ?string $toDate = null): array
    {
        // Set default date range if not provided
        $toDate = $toDate ? Carbon::parse($toDate) : Carbon::now();
        $fromDate = $fromDate ? Carbon::parse($fromDate) : $toDate->copy()->subMonths(6);
        
        // Get doctor revenue data
        $doctorRevenueData = $this->billRepository->getDoctorRevenueAnalytics($fromDate, $toDate);
        
        // Calculate average bill amount and format the response
        $formattedDoctorData = $doctorRevenueData->map(function ($item) {
            $averageBillAmount = $item->bill_count > 0 ? $item->total_revenue / $item->bill_count : 0;
            
            return [
                'doctor_id' => $item->doctor_id,
                'doctor_name' => $item->doctor_name,
                'total_revenue' => round($item->total_revenue, 2),
                'bill_count' => $item->bill_count,
                'average_bill_amount' => round($averageBillAmount, 2)
            ];
        });
        
        return [
            'doctor_revenue' => $formattedDoctorData
        ];
    }
    
    // Additional methods for the other analytics functionality...
    
    private function calculateGrowthRate($currentValue, $previousValue): float
    {
        if ($previousValue <= 0) {
            return 0;
        }
        
        return (($currentValue - $previousValue) / $previousValue) * 100;
    }
    
    private function calculateAverageBillAmount(Collection $bills): float
    {
        return $bills->count() > 0 ? $bills->sum('amount') / $bills->count() : 0;
    }
    
    private function calculateOutstandingAmount(Collection $bills): float
    {
        return $bills->filter(function ($bill) {
            return empty($bill->payment_method);
        })->sum('amount');
    }
    
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