<?php

namespace App\Http\Requests\Reminder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ScheduleReminderRequest extends FormRequest
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
     */    public function rules(): array
    {
        return [
            'appointment_id' => 'required|exists:appointments,id',
            'reminder_times' => 'sometimes|array|max:5', // Standard reminder times in minutes
            'reminder_times.*' => 'integer|min:5|max:10080', // Reminder times in minutes
            'custom_times' => 'sometimes|array|max:5', // Maximum 5 custom reminders
            'custom_times.*' => 'integer|min:5|max:10080', // Custom reminder times in minutes
            'channels' => 'required|array|min:1',
            'channels.*' => 'string|in:email,sms,push,in_app',
            'message_template' => 'sometimes|string|max:1000',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'force_reschedule' => 'sometimes|boolean'
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array
     */    public function messages(): array
    {
        return [
            'appointment_id.required' => 'Appointment ID is required.',
            'appointment_id.exists' => 'The specified appointment does not exist.',
            'reminder_times.max' => 'You can schedule a maximum of 5 reminder times.',
            'reminder_times.*.min' => 'Reminder must be at least 5 minutes before appointment.',
            'reminder_times.*.max' => 'Reminder cannot be more than 7 days (10080 minutes) before appointment.',
            'custom_times.max' => 'You can schedule a maximum of 5 custom reminder times.',
            'custom_times.*.min' => 'Custom reminder must be at least 5 minutes before appointment.',
            'custom_times.*.max' => 'Custom reminder cannot be more than 7 days (10080 minutes) before appointment.',
            'channels.required' => 'At least one notification channel is required.',
            'channels.min' => 'At least one notification channel must be selected.',
            'channels.*.in' => 'Invalid notification channel. Allowed channels: email, sms, push, in_app.',
            'message_template.max' => 'Custom message template cannot exceed 1000 characters.',
            'priority.in' => 'Priority must be one of: low, normal, high, urgent.'
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
