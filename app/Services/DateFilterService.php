<?php

namespace App\Services;

use Carbon\Carbon;

class DateFilterService
{
    /**
     * Convert a preset period to actual date range
     *
     * @param string $presetPeriod  One of: day, week, month, quarter, year
     * @param string|null $timezone User's timezone (defaults to app timezone)
     * @return array ['date_from' => string, 'date_to' => string]
     */
    public function getDateRangeFromPreset(string $presetPeriod, ?string $timezone = null): array
    {
        $now = $timezone ? Carbon::now($timezone) : Carbon::now();
        
        switch (strtolower($presetPeriod)) {
            case 'day':
            case 'today':
                $from = $now->copy()->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
                
            case 'yesterday':
                $from = $now->copy()->subDay()->startOfDay();
                $to = $now->copy()->subDay()->endOfDay();
                break;
                
            case 'week':
            case 'this_week':
                $from = $now->copy()->startOfWeek();
                $to = $now->copy()->endOfWeek();
                break;
                
            case 'last_week':
                $from = $now->copy()->subWeek()->startOfWeek();
                $to = $now->copy()->subWeek()->endOfWeek();
                break;
                
            case 'month':
            case 'this_month':
                $from = $now->copy()->startOfMonth();
                $to = $now->copy()->endOfMonth();
                break;
                
            case 'last_month':
                $from = $now->copy()->subMonth()->startOfMonth();
                $to = $now->copy()->subMonth()->endOfMonth();
                break;
                
            case 'quarter':
            case 'this_quarter':
                $from = $now->copy()->startOfQuarter();
                $to = $now->copy()->endOfQuarter();
                break;
                
            case 'year':
            case 'this_year':
                $from = $now->copy()->startOfYear();
                $to = $now->copy()->endOfYear();
                break;
                
            case 'last_year':
                $from = $now->copy()->subYear()->startOfYear();
                $to = $now->copy()->subYear()->endOfYear();
                break;
                
            default:
                throw new \InvalidArgumentException("Invalid preset period: {$presetPeriod}");
        }
        
        return [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d')
        ];
    }
    
    /**
     * Process the date filters from request
     * 
     * @param array $filters Contains potentially: date_from, date_to, preset_period
     * @param string|null $timezone User's timezone
     * @return array Updated filters with proper date_from and date_to
     */
    public function processDates(array $filters, ?string $timezone = null): array
    {
        // If preset_period is provided, it overrides manual date settings
        if (!empty($filters['preset_period'])) {
            $dateRange = $this->getDateRangeFromPreset($filters['preset_period'], $timezone);
            $filters['date_from'] = $dateRange['date_from'];
            $filters['date_to'] = $dateRange['date_to'];
            
            // For analytics compatibility
            $filters['from_date'] = $dateRange['date_from']; 
            $filters['to_date'] = $dateRange['date_to'];
        }
        
        // Ensure consistent date format
        if (!empty($filters['date_from'])) {
            $filters['date_from'] = Carbon::parse($filters['date_from'])->format('Y-m-d');
            $filters['from_date'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $filters['date_to'] = Carbon::parse($filters['date_to'])->format('Y-m-d');
            $filters['to_date'] = $filters['date_to'];
        }
        
        return $filters;
    }
}