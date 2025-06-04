<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class DateRangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'doctor_id' => 'nullable|integer|exists:users,id',
            'doctor_name' => 'nullable|string|max:100',
            'patient_id' => 'nullable|integer|exists:patients,id',
            'payment_method' => 'nullable|string',
            'service_type' => 'nullable|string',
        ];
    }
}