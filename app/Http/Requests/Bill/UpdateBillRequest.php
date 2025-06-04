<?php

namespace App\Http\Requests\Bill;

use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillRequest extends FormRequest
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
        // Get the bill from the route parameter (IDE-friendly approach)
        /** @var Bill $bill */
        $bill = request()->route('bill');
        
        return [
            'patient_id' => 'sometimes|exists:patients,id',
            'doctor_user_id' => 'sometimes|exists:users,id',
            'bill_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('bills', 'bill_number')->ignore($bill),
            ],
            'issue_date' => 'sometimes|date',
            'payment_method' => 'sometimes|string|in:cash,credit_card,insurance,bank_transfer',
            'description' => 'nullable|string',
            'regenerate_pdf' => 'nullable|boolean',
            
            // Validate items if provided
            'items' => 'nullable|array',
            'items.*.service_type' => 'required|string|max:100',
            'items.*.description' => 'nullable|string',
            'items.*.price' => 'required|numeric|min:0',
            ];
    }
}