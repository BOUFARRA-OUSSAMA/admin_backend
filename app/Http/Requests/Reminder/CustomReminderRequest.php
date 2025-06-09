<?php

namespace App\Http\Requests\Reminder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustomReminderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware and controller logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'reminder_time' => 'required|date|after:now|before:' . now()->addDays(30),
            'channels' => 'required|array|min:1',
            'channels.*' => 'string|in:email,sms,push,in_app',
            'message' => 'nullable|string|max:500',
            'reminder_type' => 'sometimes|string|in:custom,manual,follow_up',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'repeat_pattern' => 'sometimes|string|in:once,daily,weekly',
            'repeat_until' => 'nullable|date|after:reminder_time|required_if:repeat_pattern,daily,weekly'
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
            'reminder_time.required' => 'Reminder time is required.',
            'reminder_time.after' => 'Reminder time must be in the future.',
            'reminder_time.before' => 'Reminder time cannot be more than 30 days in advance.',
            'channels.required' => 'At least one notification channel must be selected.',
            'channels.min' => 'At least one notification channel must be selected.',
            'channels.*.in' => 'Invalid notification channel. Allowed channels: email, sms, push, in_app.',
            'message.max' => 'Custom message cannot exceed 500 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'reminder_type.in' => 'Invalid reminder type. Allowed types: custom, manual, follow_up.',
            'priority.in' => 'Priority must be one of: low, normal, high, urgent.',
            'repeat_pattern.in' => 'Repeat pattern must be one of: once, daily, weekly.',
            'repeat_until.after' => 'Repeat end date must be after the reminder time.',
            'repeat_until.required_if' => 'Repeat end date is required when using daily or weekly repeat patterns.'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validate that reminder time is before appointment (will be checked in controller with appointment data)
            if ($this->has('appointment_datetime_start')) {
                $reminderTime = strtotime($this->reminder_time);
                $appointmentTime = strtotime($this->appointment_datetime_start);
                
                if ($reminderTime >= $appointmentTime) {
                    $validator->errors()->add('reminder_time', 'Reminder time must be before the appointment time.');
                }
            }
        });
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
