<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationController extends Controller
{
    /**
     * Get user's notifications with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'per_page' => 'sometimes|integer|min:1|max:50',
                'read_status' => 'sometimes|in:all,read,unread',
                'type' => 'sometimes|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $perPage = $request->get('per_page', 15);
            $readStatus = $request->get('read_status', 'all');
            $type = $request->get('type');

            $query = $user->notifications();

            // Filter by read status
            if ($readStatus === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($readStatus === 'unread') {
                $query->whereNull('read_at');
            }

            // Filter by type
            if ($type) {
                $query->where('type', $type);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                                 ->paginate($perPage);            // Transform the notifications for better frontend consumption
            $transformedNotifications = $notifications->getCollection()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'system', // Use semantic type from data
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'appointment_id' => $notification->data['appointment_id'] ?? null,
                    'reminder_type' => $notification->data['reminder_type'] ?? null,
                    'appointment_date' => $notification->data['appointment_date'] ?? null,
                    'action_url' => $notification->data['action_url'] ?? null,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'is_read' => !is_null($notification->read_at),
                    'time_ago' => $notification->created_at->diffForHumans()
                ];
            });

            $notifications->setCollection($transformedNotifications);

            return response()->json([
                'success' => true,
                'data' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem()
                ],
                'summary' => [
                    'total_notifications' => $user->notifications()->count(),
                    'unread_count' => $user->unreadNotifications()->count(),
                    'read_count' => $user->readNotifications()->count()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get count of unread notifications (for badge)
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $unreadCount = $user->unreadNotifications()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount,
                    'has_unread' => $unreadCount > 0
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $notification = $user->notifications()->find($notificationId);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            if (is_null($notification->read_at)) {
                $notification->markAsRead();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read',
                    'data' => [
                        'notification_id' => $notificationId,
                        'read_at' => $notification->fresh()->read_at,
                        'remaining_unread' => $user->unreadNotifications()->count()
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification was already read',
                'data' => [
                    'notification_id' => $notificationId,
                    'read_at' => $notification->read_at,
                    'remaining_unread' => $user->unreadNotifications()->count()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $unreadCount = $user->unreadNotifications()->count();
            
            if ($unreadCount > 0) {
                $user->unreadNotifications()->update(['read_at' => now()]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Marked {$unreadCount} notifications as read",
                    'data' => [
                        'marked_count' => $unreadCount,
                        'remaining_unread' => 0
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'No unread notifications to mark',
                'data' => [
                    'marked_count' => 0,
                    'remaining_unread' => 0
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific notification
     */    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        Log::info('Destroy method called with ID: ' . $notificationId);
        
        try {
            $user = Auth::user();
            
            $notification = $user->notifications()->find($notificationId);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $wasUnread = is_null($notification->read_at);
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully',
                'data' => [
                    'notification_id' => $notificationId,
                    'was_unread' => $wasUnread,
                    'remaining_unread' => $user->unreadNotifications()->count(),
                    'remaining_total' => $user->notifications()->count()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }    /**
     * Clear all read notifications (cleanup)
     */    public function clearRead(Request $request): JsonResponse
    {
        Log::info('ClearRead method called successfully!');
        
        try {
            $user = Auth::user();
              // Debug logging
            Log::info('ClearRead Debug', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'request_path' => $request->path(),
                'request_method' => $request->method()
            ]);
            
            $readCount = $user->readNotifications()->count();
            
            Log::info('ClearRead Counts', [
                'read_count' => $readCount,
                'total_notifications' => $user->notifications()->count()
            ]);
              if ($readCount > 0) {
                // Direct database delete approach (more reliable)
                $deletedCount = DB::table('notifications')
                    ->where('notifiable_type', get_class($user))
                    ->where('notifiable_id', $user->id)
                    ->whereNotNull('read_at')
                    ->delete();
                  Log::info('ClearRead Success', [
                    'deleted_count' => $deletedCount
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Cleared {$deletedCount} read notifications",
                    'data' => [
                        'cleared_count' => $deletedCount,
                        'remaining_total' => $user->notifications()->count(),
                        'remaining_unread' => $user->unreadNotifications()->count()
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'No read notifications to clear',
                'data' => [
                    'cleared_count' => 0,
                    'remaining_total' => $user->notifications()->count(),
                    'remaining_unread' => $user->unreadNotifications()->count()
                ]
            ]);        } catch (Exception $e) {
            Log::error('ClearRead Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
              return response()->json([
                'success' => false,
                'message' => 'Failed to clear read notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }    /**
     * Get upcoming reminders for authenticated user
     */
    public function getUpcomingReminders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Use existing ScheduledReminderJob model with existing relationships
            $upcomingReminders = \App\Models\ScheduledReminderJob::with(['appointment.doctor'])
                ->whereHas('appointment', function($q) use ($user) {
                    $q->where('patient_user_id', $user->id);
                })
                ->where('status', 'pending')
                ->where('scheduled_for', '>', now())
                ->orderBy('scheduled_for', 'asc')
                ->take(20)
                ->get()
                ->map(function($job) {
                    return [
                        'id' => $job->id,
                        'reminder_type' => $job->reminder_type,
                        'scheduled_for' => $job->scheduled_for->toISOString(),
                        'appointment' => [
                            'id' => $job->appointment->id,
                            'date' => $job->appointment->appointment_datetime_start->toDateString(),
                            'time' => $job->appointment->appointment_datetime_start->format('H:i'),
                            'type' => $job->appointment->type ?? 'consultation',
                            'status' => $job->appointment->status ?? 'scheduled',
                            'doctor' => $job->appointment->doctor ? [
                                'first_name' => $job->appointment->doctor->first_name ?? 'Unknown',
                                'last_name' => $job->appointment->doctor->last_name ?? 'Doctor',
                                'specialization' => $job->appointment->doctor->specialization ?? 'General Medicine'
                            ] : null
                        ],
                        'message' => "Reminder for your {$job->reminder_type} appointment",
                        'time_until' => $job->scheduled_for->diffForHumans(),
                        'can_cancel' => true,
                        'can_reschedule' => true,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $upcomingReminders,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 20,
                'total' => $upcomingReminders->count(),
                'message' => 'Upcoming reminders retrieved successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching upcoming reminders', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch upcoming reminders: ' . $e->getMessage()
            ], 500);
        }
    }
}
