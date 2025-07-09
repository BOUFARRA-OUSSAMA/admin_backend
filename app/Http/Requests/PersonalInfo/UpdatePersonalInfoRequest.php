<?php

namespace App\Http\Requests\PersonalInfo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdatePersonalInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'surname' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|string|max:30', 
            'birthdate' => 'sometimes|nullable|date|before:today',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'address' => 'sometimes|nullable|string|max:1000',
            'emergency_contact' => 'sometimes|nullable|string|max:500',
            'marital_status' => 'sometimes|nullable|in:single,married,divorced,widowed,other',
            'blood_type' => 'sometimes|nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'nationality' => 'sometimes|nullable|string|max:100',
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already taken.',
            'birthdate.before' => 'The birthdate must be a date before today.',
            'gender.in' => 'The gender must be one of: male, female, other.',
            'marital_status.in' => 'The marital status must be one of: single, married, divorced, widowed.',
            'blood_type.in' => 'The blood type must be a valid blood type.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}