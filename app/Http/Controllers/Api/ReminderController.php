<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReminderService;
use App\Models\Appointment;
use App\Models\ReminderSetting;
use App\Models\ReminderLog;
use App\Models\ReminderAnalytics;
use App\Http\Requests\Reminder\UpdateReminderSettingsRequest;
use App\Http\Requests\Reminder\ScheduleReminderRequest;
use App\Http\Requests\Reminder\BulkReminderOperationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class ReminderController extends Controller
{
    protected ReminderService $reminderService;    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Get reminder settings for authenticated user
     */
    public function getReminderSettings(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $settings = $this->reminderService->getReminderSettings($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                    'user_id' => $user->id,
                    'created_at' => $settings->created_at,
                    'updated_at' => $settings->updated_at
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reminder settings',
                'error' => $e->getMessage()            ], 500);
        }
    }

    /**
     * Update reminder settings for authenticated user
     */
    public function updateReminderSettings(UpdateReminderSettingsRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $settings = $this->reminderService->updateReminderSettings(
                $user->id, 
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Reminder settings updated successfully',
                'data' => $settings
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reminder settings',
                'error' => $e->getMessage()            ], 400);
        }
    }

    /**
     * Schedule reminders for a specific appointment (Admin/Staff)
     */
    public function scheduleReminders(ScheduleReminderRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $appointment = Appointment::findOrFail($validatedData['appointment_id']);
            
            $result = $this->reminderService->scheduleReminders(
                $appointment, 
                $validatedData
            );

            return response()->json([
                'success' => true,
                'message' => 'Reminders scheduled successfully',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'scheduled_count' => $result['scheduled_count'],
                    'reminder_times' => $result['reminder_times'],
                    'channels' => $result['channels']
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule reminders',
                'error' => $e->getMessage()        ], 400);
        }
    }

    /**
     * Cancel scheduled reminders for an appointment (Admin/Staff)
     */
    public function cancelReminders(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'appointment_id' => 'required|exists:appointments,id',
                'reason' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointmentId = $request->input('appointment_id');
            $reason = $request->input('reason', 'Manual cancellation');
            
            $result = $this->reminderService->cancelReminders($appointmentId, $reason);            return response()->json([
                'success' => true,
                'message' => 'Reminders cancelled successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel reminders',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Send immediate test reminder (Admin/Staff)
     */
    public function sendTestReminder(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'appointment_id' => 'required|exists:appointments,id',
                'channels' => 'required|array|min:1',
                'channels.*' => 'string|in:email,sms,push,in_app'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointment = Appointment::findOrFail($request->appointment_id);
            
            $result = $this->reminderService->sendImmediateReminder(
                $appointment, 
                $request->channels,
                'Test reminder sent by ' . Auth::user()->name
            );

            return response()->json([
                'success' => true,
                'message' => 'Test reminder sent successfully',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'sent_channels' => $result['sent_channels'],
                    'failed_channels' => $result['failed_channels']
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test reminder',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get reminder logs with filters
     */
    public function getLogs(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'appointment_id' => 'sometimes|exists:appointments,id',
                'user_id' => 'sometimes|exists:users,id',
                'delivery_status' => 'sometimes|in:pending,sent,failed,cancelled',
                'reminder_type' => 'sometimes|in:24_hour,2_hour,custom',
                'channel' => 'sometimes|in:email,sms,push,in_app',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from',
                'per_page' => 'sometimes|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $validator->validated();
            $perPage = $filters['per_page'] ?? 15;
            unset($filters['per_page']);

            $logs = ReminderLog::query()
                ->with(['appointment.patient', 'appointment.doctor'])
                ->when(isset($filters['appointment_id']), function($query) use ($filters) {
                    return $query->where('appointment_id', $filters['appointment_id']);
                })
                ->when(isset($filters['user_id']), function($query) use ($filters) {
                    return $query->whereHas('appointment', function($q) use ($filters) {
                        $q->where('patient_user_id', $filters['user_id'])
                          ->orWhere('doctor_user_id', $filters['user_id']);
                    });
                })
                ->when(isset($filters['delivery_status']), function($query) use ($filters) {
                    return $query->where('delivery_status', $filters['delivery_status']);
                })
                ->when(isset($filters['reminder_type']), function($query) use ($filters) {
                    return $query->where('reminder_type', $filters['reminder_type']);
                })
                ->when(isset($filters['channel']), function($query) use ($filters) {
                    return $query->where('channel', $filters['channel']);
                })
                ->when(isset($filters['date_from']), function($query) use ($filters) {
                    return $query->whereDate('scheduled_for', '>=', $filters['date_from']);
                })
                ->when(isset($filters['date_to']), function($query) use ($filters) {
                    return $query->whereDate('scheduled_for', '<=', $filters['date_to']);
                })
                ->orderBy('scheduled_for', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reminder logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reminder analytics
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'sometimes|in:day,week,month,quarter,year',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from',
                'doctor_id' => 'sometimes|exists:users,id',
                'channel' => 'sometimes|in:email,sms,push,in_app'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $validator->validated();
            $analytics = $this->reminderService->getAnalytics($filters);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'period' => $filters['period'] ?? 'month',
                'filters_applied' => $filters
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's upcoming reminders
     */
    public function getUpcomingReminders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $limit = $request->get('limit', 10);

            $reminders = ReminderLog::query()
                ->whereHas('appointment', function($query) use ($user) {
                    $query->where('patient_user_id', $user->id);
                })
                ->where('delivery_status', 'pending')
                ->where('scheduled_for', '>', now())
                ->with(['appointment.doctor'])
                ->orderBy('scheduled_for', 'asc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reminders,
                'total_upcoming' => $reminders->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming reminders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Opt out of specific reminder type for an appointment
     */
    public function optOutReminder(Request $request, $appointmentId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reminder_type' => 'required|in:24_hour,2_hour,custom,all',
                'channels' => 'sometimes|array',
                'channels.*' => 'string|in:email,sms,push,in_app'
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

            // Verify user owns this appointment
            if ($appointment->patient_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only opt out of your own appointment reminders'
                ], 403);
            }

            $result = $this->reminderService->optOutReminders(
                $appointment,
                $request->reminder_type,
                $request->channels ?? ['email', 'sms', 'push', 'in_app']
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully opted out of reminders',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'cancelled_count' => $result['cancelled_count']
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to opt out of reminders',
                'error' => $e->getMessage()            ], 400);
        }
    }

    /**
     * Bulk operations for reminders (Admin only)
     */
    public function bulkOperations(BulkReminderOperationRequest $request): JsonResponse
    {
        try {
            $results = [];
            $successCount = 0;
            $failedCount = 0;

            foreach ($request->appointment_ids as $appointmentId) {
                try {
                    $appointment = Appointment::findOrFail($appointmentId);
                    
                    switch ($request->operation) {
                        case 'schedule':
                            $result = $this->reminderService->scheduleReminders(
                                $appointment, 
                                $request->options ?? []
                            );
                            break;
                        
                        case 'cancel':
                            $result = $this->reminderService->cancelScheduledReminders($appointment);
                            break;
                        
                        case 'reschedule':
                            // Cancel existing and schedule new
                            $this->reminderService->cancelScheduledReminders($appointment);
                            $result = $this->reminderService->scheduleReminders(
                                $appointment, 
                                $request->options ?? []
                            );
                            break;

                        case 'test':
                            $result = $this->reminderService->sendImmediateReminder(
                                $appointment,
                                $request->options['channels'] ?? ['email'],
                                'Bulk test reminder',
                                true
                            );
                            break;
                    }
                    
                    $results[] = [
                        'appointment_id' => $appointmentId,
                        'status' => 'success',
                        'result' => $result
                    ];
                    $successCount++;
                    
                } catch (Exception $e) {
                    $results[] = [
                        'appointment_id' => $appointmentId,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    $failedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Bulk operation completed: {$successCount} successful, {$failedCount} failed",
                'data' => [
                    'operation' => $request->operation,
                    'total_processed' => count($request->appointment_ids),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'results' => $results
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk operation',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
