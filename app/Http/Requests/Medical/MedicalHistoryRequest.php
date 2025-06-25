<?php


namespace App\Http\Requests\Medical;

use Illuminate\Foundation\Http\FormRequest;

class MedicalHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:patients,id',
            'current_medical_conditions' => 'nullable|array',
            'current_medical_conditions.*' => 'string|max:255',
            'past_surgeries' => 'nullable|array',
            'past_surgeries.*' => 'string|max:255',
            'chronic_diseases' => 'nullable|array',
            'chronic_diseases.*' => 'string|max:255',
            'current_medications' => 'nullable|array',
            'current_medications.*' => 'string|max:255',
            'allergies' => 'nullable|array',
            'allergies.*' => 'string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Patient ID is required',
            'patient_id.exists' => 'Invalid patient ID',
            'current_medical_conditions.array' => 'Medical conditions must be an array',
            'past_surgeries.array' => 'Past surgeries must be an array',
            'chronic_diseases.array' => 'Chronic diseases must be an array',
            'current_medications.array' => 'Current medications must be an array',
            'allergies.array' => 'Allergies must be an array',
        ];
    }
}