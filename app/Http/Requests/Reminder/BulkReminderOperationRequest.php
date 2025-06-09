<?php

namespace App\Http\Requests\Reminder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkReminderOperationRequest extends FormRequest
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
            'operation' => 'required|in:schedule,cancel,reschedule,test',
            'appointment_ids' => 'required|array|min:1|max:100',
            'appointment_ids.*' => 'integer|exists:appointments,id',
            'options' => 'sometimes|array',
            'options.channels' => 'sometimes|array|min:1',
            'options.channels.*' => 'string|in:email,sms,push,in_app',
            'options.custom_times' => 'sometimes|array|max:5',
            'options.custom_times.*' => 'integer|min:5|max:10080',
            'options.message_template' => 'sometimes|string|max:1000',
            'options.priority' => 'sometimes|string|in:low,normal,high,urgent',
            'options.force_reschedule' => 'sometimes|boolean',
            'reason' => 'sometimes|string|max:500'
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
            'operation.required' => 'Operation type is required.',
            'operation.in' => 'Invalid operation. Allowed operations: schedule, cancel, reschedule, test.',
            'appointment_ids.required' => 'At least one appointment ID is required.',
            'appointment_ids.min' => 'At least one appointment must be selected.',
            'appointment_ids.max' => 'Cannot process more than 100 appointments at once.',
            'appointment_ids.*.exists' => 'One or more appointment IDs are invalid.',
            'options.channels.min' => 'At least one notification channel must be selected.',
            'options.channels.*.in' => 'Invalid notification channel. Allowed channels: email, sms, push, in_app.',
            'options.custom_times.max' => 'Maximum 5 custom reminder times allowed.',
            'options.custom_times.*.min' => 'Custom reminder must be at least 5 minutes before appointment.',
            'options.custom_times.*.max' => 'Custom reminder cannot be more than 7 days before appointment.',
            'options.message_template.max' => 'Message template cannot exceed 1000 characters.',
            'options.priority.in' => 'Priority must be one of: low, normal, high, urgent.',
            'reason.max' => 'Reason cannot exceed 500 characters.'
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
