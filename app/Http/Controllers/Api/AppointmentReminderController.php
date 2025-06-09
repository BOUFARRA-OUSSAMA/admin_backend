<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReminderService;
use App\Models\Appointment;
use App\Http\Requests\Reminder\CustomReminderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class AppointmentReminderController extends Controller
{
    protected ReminderService $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Get reminders for a specific appointment
     */
    public function getAppointmentReminders(Request $request, $appointmentId): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $user = Auth::user();

            // Check if user has access to this appointment
            $hasAccess = $appointment->patient_user_id === $user->id || 
                        $appointment->doctor_user_id === $user->id ||
                        $user->hasRole(['admin', 'receptionist']);

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this appointment'
                ], 403);
            }

            $reminders = $this->reminderService->getAppointmentReminders($appointment);

            return response()->json([
                'success' => true,
                'data' => [
                    'appointment_id' => $appointment->id,
                    'appointment_datetime' => $appointment->appointment_datetime_start,
                    'patient_name' => $appointment->patient->name,
                    'doctor_name' => $appointment->doctor->name,
                    'reminders' => $reminders,
                    'total_reminders' => count($reminders)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve appointment reminders',
                'error' => $e->getMessage()            ], 500);
        }
    }

    /**
     * Schedule custom reminder for appointment
     */
    public function scheduleCustomReminder(CustomReminderRequest $request, $appointmentId): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $user = Auth::user();

            // Only admin/staff can schedule custom reminders
            if (!$user->hasRole(['admin', 'receptionist', 'doctor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to schedule custom reminders'
                ], 403);
            }

            // Validate reminder time is before appointment
            if (strtotime($request->reminder_time) >= strtotime($appointment->appointment_datetime_start)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reminder time must be before the appointment time'
                ], 422);
            }

            $reminderData = array_merge($request->validated(), [
                'scheduled_by_user_id' => $user->id
            ]);

            $result = $this->reminderService->scheduleCustomReminder($appointment, $reminderData);

            return response()->json([
                'success' => true,
                'message' => 'Custom reminder scheduled successfully',
                'data' => [
                    'reminder_id' => $result['reminder_id'],
                    'appointment_id' => $appointment->id,
                    'scheduled_for' => $request->reminder_time,
                    'channels' => $request->channels,
                    'scheduled_by' => $user->name
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule custom reminder',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel specific reminder
     */
    public function cancelReminder(Request $request, $appointmentId, $reminderId): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $user = Auth::user();

            // Check permissions
            $canCancel = $user->hasRole(['admin', 'receptionist', 'doctor']) ||
                        ($appointment->patient_user_id === $user->id);

            if (!$canCancel) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to cancel this reminder'
                ], 403);
            }

            $result = $this->reminderService->cancelSpecificReminder($reminderId, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Reminder cancelled successfully',
                'data' => [
                    'reminder_id' => $reminderId,
                    'appointment_id' => $appointment->id,
                    'cancelled_by' => $user->name,
                    'cancelled_at' => now()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel reminder',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reschedule specific reminder
     */
    public function rescheduleReminder(Request $request, $appointmentId, $reminderId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'new_reminder_time' => 'required|date|before:' . now()->addDays(30),
                'reason' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointment = Appointment::findOrFail($appointmentId);
            $user = Auth::user();

            // Only admin/staff can reschedule reminders
            if (!$user->hasRole(['admin', 'receptionist', 'doctor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to reschedule reminders'
                ], 403);
            }

            // Validate new reminder time is before appointment
            if (strtotime($request->new_reminder_time) >= strtotime($appointment->appointment_datetime_start)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New reminder time must be before the appointment time'
                ], 422);
            }

            $result = $this->reminderService->rescheduleReminder(
                $reminderId, 
                $request->new_reminder_time,
                $user->id,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Reminder rescheduled successfully',
                'data' => [
                    'reminder_id' => $reminderId,
                    'appointment_id' => $appointment->id,
                    'old_time' => $result['old_time'],
                    'new_time' => $request->new_reminder_time,
                    'rescheduled_by' => $user->name,
                    'reason' => $request->reason
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reschedule reminder',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Test reminder delivery for appointment
     */
    public function testReminderDelivery(Request $request, $appointmentId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'channels' => 'required|array|min:1',
                'channels.*' => 'string|in:email,sms,push,in_app',
                'test_message' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointment = Appointment::findOrFail($appointmentId);
            $user = Auth::user();

            // Only admin/staff can test reminders
            if (!$user->hasRole(['admin', 'receptionist', 'doctor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to test reminders'
                ], 403);
            }

            $testMessage = $request->test_message ?? "This is a test reminder for appointment on " . 
                          $appointment->appointment_datetime_start->format('Y-m-d H:i');

            $result = $this->reminderService->sendImmediateReminder(
                $appointment,
                $request->channels,
                $testMessage,
                true // Mark as test
            );

            return response()->json([
                'success' => true,
                'message' => 'Test reminder sent successfully',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'tested_channels' => $request->channels,
                    'delivery_results' => $result,
                    'tested_by' => $user->name,
                    'test_time' => now()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test reminder',
                'error' => $e->getMessage()
            ], 400);
        }
    }    /**
     * Get reminder delivery status for appointment
     */
    public function getReminderDeliveryStatus(Request $request, $appointmentId): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $user = Auth::user();

            // Check access permissions
            $hasAccess = $appointment->patient_user_id === $user->id || 
                        $appointment->doctor_user_id === $user->id ||
                        $user->hasRole(['admin', 'receptionist']);

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this appointment'
                ], 403);
            }

            $status = $this->reminderService->getReminderDeliveryStatus($appointment->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'appointment_id' => $appointment->id,
                    'appointment_status' => $appointment->status,
                    'appointment_datetime' => $appointment->appointment_datetime_start,
                    'reminder_summary' => $status['summary'],
                    'delivery_breakdown' => $status['breakdown'],
                    'last_reminder_sent' => $status['last_sent'],
                    'next_reminder_scheduled' => $status['next_scheduled'],
                    'total_reminders_sent' => $status['total_sent'],
                    'failed_deliveries' => $status['failed_count']
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reminder status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update reminder preferences for specific appointment
     */
    public function updateAppointmentReminderPreferences(Request $request, $appointmentId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reminder_24h_enabled' => 'sometimes|boolean',
                'reminder_2h_enabled' => 'sometimes|boolean',
                'preferred_channels' => 'sometimes|array',
                'preferred_channels.*' => 'string|in:email,sms,push,in_app',
                'opt_out_all' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointment = Appointment::findOrFail($appointmentId);
            $user = Auth::user();

            // Only patient can update their own appointment reminder preferences
            if ($appointment->patient_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update reminder preferences for your own appointments'
                ], 403);
            }

            $result = $this->reminderService->updateAppointmentReminderPreferences(
                $appointment,
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Appointment reminder preferences updated successfully',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'updated_preferences' => $result['preferences'],
                    'affected_reminders' => $result['affected_count']
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reminder preferences',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Acknowledge received reminder (marks as read/acknowledged)
     */
    public function acknowledgeReminder(Request $request, $appointmentId, $reminderId): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            $user = Auth::user();

            // Only patient can acknowledge their reminders
            if ($appointment->patient_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only acknowledge your own reminders'
                ], 403);
            }

            $result = $this->reminderService->acknowledgeReminder($reminderId, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Reminder acknowledged successfully',
                'data' => [
                    'reminder_id' => $reminderId,
                    'appointment_id' => $appointment->id,
                    'acknowledged_by' => $user->name,
                    'acknowledged_at' => now()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge reminder',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
