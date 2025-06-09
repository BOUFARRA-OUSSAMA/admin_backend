<?php

namespace App\Http\Requests\Reminder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateReminderSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'email_enabled' => 'sometimes|boolean',
            'sms_enabled' => 'sometimes|boolean',
            'push_enabled' => 'sometimes|boolean',
            'in_app_enabled' => 'sometimes|boolean',
            'reminder_24h_enabled' => 'sometimes|boolean',
            'reminder_2h_enabled' => 'sometimes|boolean',
            'reminder_custom_enabled' => 'sometimes|boolean',
            'custom_reminder_minutes' => 'nullable|integer|min:5|max:10080', // 5 minutes to 7 days
            'quiet_hours_enabled' => 'sometimes|boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'timezone' => 'sometimes|string|max:50'
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
            'custom_reminder_minutes.min' => 'Custom reminder time must be at least 5 minutes before appointment.',
            'custom_reminder_minutes.max' => 'Custom reminder time cannot be more than 7 days (10080 minutes) before appointment.',
            'quiet_hours_start.date_format' => 'Quiet hours start time must be in HH:MM format.',
            'quiet_hours_end.date_format' => 'Quiet hours end time must be in HH:MM format.',
            'timezone.max' => 'Timezone string cannot exceed 50 characters.'
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
