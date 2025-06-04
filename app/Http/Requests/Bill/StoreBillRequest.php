<?php

namespace App\Http\Requests\Bill;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'doctor_user_id' => 'required|exists:users,id',
            'bill_number' => 'nullable|string|max:50|unique:bills,bill_number',
            'issue_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,credit_card,insurance,bank_transfer',
            'description' => 'nullable|string',
            'generate_pdf' => 'nullable|boolean',
            
            // Validate items if provided
            'items' => 'nullable|array',
            'items.*.service_type' => 'required|string|max:100',
            'items.*.description' => 'nullable|string',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }
}