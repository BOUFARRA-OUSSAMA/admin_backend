<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class RevenueAnalyticsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'timeframe' => 'nullable|in:daily,weekly,monthly,yearly,day,week,month,quarter,year',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'year' => 'nullable|integer|min:2000|max:2100',
            'preset_period' => 'nullable|string|in:day,today,yesterday,week,this_week,last_week,month,this_month,last_month,quarter,this_quarter,year,this_year,last_year',
            'doctor_id' => 'nullable|integer|exists:users,id',
            'doctor_name' => 'nullable|string|max:100',
            'patient_id' => 'nullable|integer|exists:patients,id',
            'payment_method' => 'nullable|string',
            'service_type' => 'nullable|string',
        ];
    }
}