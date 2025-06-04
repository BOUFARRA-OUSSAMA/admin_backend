<?php
// filepath: c:\Users\Sefanos\Desktop\n8n\Frontend\admin_backend\app\Http\Controllers\Api\DoctorAppointmentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DoctorAppointmentService;
use App\Models\Appointment;
use App\Models\BlockedTimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class DoctorAppointmentController extends Controller
{
    protected DoctorAppointmentService $doctorAppointmentService;

    public function __construct(DoctorAppointmentService $doctorAppointmentService)
    {
        $this->doctorAppointmentService = $doctorAppointmentService;
    }

    /**
     * Get doctor's appointments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'upcoming' => $request->boolean('upcoming'),
            ];

            $perPage = $request->get('per_page', 15);
            $appointments = $this->doctorAppointmentService->getMyAppointments(Auth::user(), $filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $appointments->items(),
                'pagination' => [
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                ],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's schedule
     */
    public function todaysSchedule(): JsonResponse
    {
        try {
            $schedule = $this->doctorAppointmentService->getTodaysSchedule(Auth::user());

            return response()->json([
                'success' => true,
                'data' => $schedule,
                'date' => now()->format('Y-m-d'),
                'total_appointments' => $schedule->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve today\'s schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upcoming appointments
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $appointments = $this->doctorAppointmentService->getUpcomingAppointments(Auth::user(), $days);

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'days_ahead' => $days
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedule for specific date
     */
    public function scheduleForDate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $schedule = $this->doctorAppointmentService->getScheduleForDate(Auth::user(), $request->date);

            return response()->json([
                'success' => true,
                'data' => $schedule
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm appointment
     */
    public function confirm(Request $request, $id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'confirmation_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->doctorAppointmentService->confirmAppointment(Auth::user(), $appointment);
            
            if ($result) {
                // Update with confirmation notes if provided
                if ($request->has('confirmation_notes')) {
                    $appointment->update([
                        'notes_by_staff' => $request->confirmation_notes,
                        'last_updated_by_user_id' => Auth::id()
                    ]);
                }

                // ✅ REMOVED: timeSlot from fresh() relationships
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment confirmed successfully',
                    'data' => [
                        'appointment' => $appointment->fresh(['patient']),
                        'confirmed_at' => now(),
                        'confirmed_by' => Auth::user()->name
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm appointment'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm appointment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Complete appointment
     */
    public function complete(Request $request, $id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'notes_by_staff' => 'nullable|string|max:1000',
                'outcome' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notes = $request->notes_by_staff;
            $result = $this->doctorAppointmentService->completeAppointment(Auth::user(), $appointment, $notes);
            
            if ($result) {
                // Update with outcome if provided
                if ($request->has('outcome')) {
                    $appointment->update([
                        'outcome' => $request->outcome,
                        'last_updated_by_user_id' => Auth::id()
                    ]);
                }

                // ✅ REMOVED: timeSlot from fresh() relationships
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment completed successfully',
                    'data' => [
                        'appointment' => $appointment->fresh(['patient']),
                        'completed_at' => now(),
                        'completed_by' => Auth::user()->name
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete appointment'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete appointment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel appointment
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->doctorAppointmentService->cancelAppointment(Auth::user(), $appointment, $request->reason);
            
            if ($result) {
                // ✅ REMOVED: timeSlot from fresh() relationships
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment cancelled successfully',
                    'data' => [
                        'appointment' => $appointment->fresh(['patient']),
                        'cancelled_at' => now(),
                        'cancelled_by' => Auth::user()->name,
                        'cancellation_reason' => $request->reason
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel appointment'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel appointment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Mark appointment as no-show
     */
    public function markNoShow(Request $request, $id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->doctorAppointmentService->markAsNoShow(Auth::user(), $appointment);
            
            if ($result) {
                // Update with notes if provided
                if ($request->has('notes')) {
                    $appointment->update([
                        'notes_by_staff' => $request->notes,
                        'last_updated_by_user_id' => Auth::id()
                    ]);
                }

                // ✅ REMOVED: timeSlot from fresh() relationships
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment marked as no-show',
                    'data' => [
                        'appointment' => $appointment->fresh(['patient']),
                        'marked_at' => now(),
                        'marked_by' => Auth::user()->name
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark appointment as no-show'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark appointment as no-show',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Block time slots
     */
    public function blockTimeSlots(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_datetime' => 'required|date|after_or_equal:now',
                'end_datetime' => 'required|date|after:start_datetime',
                'reason' => 'required|string|max:255',
                'is_recurring' => 'boolean',
                'recurring_pattern' => 'required_if:is_recurring,true|in:daily,weekly,monthly',
                'recurring_until' => 'nullable|date|after:end_datetime',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $blockedSlot = $this->doctorAppointmentService->blockTimeSlots(Auth::user(), $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Time slots blocked successfully',
                'data' => $blockedSlot
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to block time slots',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get availability for date range
     */
    public function availability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $availability = $this->doctorAppointmentService->getAvailability(
                Auth::user(), 
                $request->start_date, 
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $availability,
                'date_range' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get doctor statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month');
            $stats = $this->doctorAppointmentService->getDoctorStats(Auth::user(), $period);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get blocked time slots (supports both single date and date range)
     */
    public function blockedSlots(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'nullable|date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = BlockedTimeSlot::where('doctor_user_id', Auth::id());

            // Support both single date and date range queries
            if ($request->has('start_date') && $request->has('end_date')) {
                // Date range query (for week/month views)
                $query->whereBetween('start_datetime', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
                $dateInfo = [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'query_type' => 'range'
                ];
            } else {
                // Single date query (for day view)
                $date = $request->get('date', now()->format('Y-m-d'));
                $query->whereDate('start_datetime', $date);
                $dateInfo = [
                    'date' => $date,
                    'query_type' => 'single'
                ];
            }

            $blockedSlots = $query->orderBy('start_datetime', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $blockedSlots,
                'total_blocked_slots' => $blockedSlots->count(),
                'date_info' => $dateInfo
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve blocked slots',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove blocked time slot
     */
    public function unblockTimeSlot($id): JsonResponse
    {
        try {
            $blockedSlot = BlockedTimeSlot::where('doctor_user_id', Auth::id())
                ->findOrFail($id);

            $blockedSlot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Time slot unblocked successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unblock time slot',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Create appointment for patient (doctor initiated) - NO TimeSlot dependency
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_user_id' => 'required|exists:users,id',
                'appointment_datetime_start' => 'required|date|after:now',
                'appointment_datetime_end' => 'required|date|after:appointment_datetime_start',
                'type' => 'required|string|in:consultation,follow-up,procedure,emergency,therapy',
                'reason_for_visit' => 'required|string|max:500',
                'priority' => 'nullable|string|in:low,normal,high,urgent',
                'notes_by_staff' => 'nullable|string|max:1000',
                'reminder_preference' => 'nullable|string|in:email,sms,both,none'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointmentData = array_merge($validator->validated(), [
                'doctor_user_id' => Auth::id()
            ]);

            $appointment = $this->doctorAppointmentService->createAppointment(Auth::user(), $appointmentData);

            return response()->json([
                'success' => true,
                'message' => 'Appointment created successfully',
                'data' => [
                    'appointment' => $appointment,
                    'appointment_id' => $appointment->id,
                    'patient_name' => $appointment->patient->name,
                    'appointment_datetime' => $appointment->appointment_datetime_start->format('Y-m-d H:i'),
                    'status' => $appointment->status
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create appointment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Create recurring appointments - NO TimeSlot dependency
     */
    public function createRecurring(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_user_id' => 'required|exists:users,id',
                'start_date' => 'required|date|after_or_equal:today',
                'type' => 'required|string|in:consultation,follow-up,procedure,therapy,treatment',
                'reason_for_visit' => 'required|string|max:500',
                'pattern' => 'required|array',
                'pattern.frequency' => 'required|string|in:daily,weekly,monthly',
                'pattern.total_sessions' => 'required|integer|min:2|max:52',
                'pattern.duration_minutes' => 'required|integer|min:15|max:240',
                'pattern.time' => 'required|date_format:H:i:s',
                'pattern.day_of_week' => 'required_if:pattern.frequency,weekly|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'notes_by_staff' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $recurringData = $validator->validated();
            $result = $this->doctorAppointmentService->createRecurringAppointments(Auth::user(), $recurringData);

            return response()->json([
                'success' => true,
                'message' => "Successfully created {$result['total_created']} out of {$result['total_requested']} appointments",
                'data' => [
                    'appointments' => $result['appointments'],
                    'summary' => [
                        'total_requested' => $result['total_requested'],
                        'total_created' => $result['total_created'],
                        'success_rate' => $result['success_rate'],
                        'errors' => $result['errors']
                    ],
                    'pattern' => $recurringData['pattern'],
                    'patient_name' => $result['appointments'][0]->patient->name ?? 'Unknown'
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create recurring appointments',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get available patients for appointment booking
     */
    public function getAvailablePatients(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $limit = $request->get('limit', 20);

            $patients = \App\Models\User::whereHas('roles', function($query) {
                    $query->where('code', 'patient');
                })
                ->when($search, function($query) use ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->select('id', 'name', 'email', 'phone')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $patients,
                'total' => $patients->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check appointment conflicts for a time slot
     */
    public function checkConflicts(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_datetime' => 'required|date',
                'end_datetime' => 'required|date|after:start_datetime'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startTime = Carbon::parse($request->start_datetime);
            $endTime = Carbon::parse($request->end_datetime);

            // Check appointment conflicts
            $conflicts = Appointment::where('doctor_user_id', Auth::id())
                ->where(function($query) use ($startTime, $endTime) {
                    $query->whereBetween('appointment_datetime_start', [$startTime, $endTime])
                          ->orWhereBetween('appointment_datetime_end', [$startTime, $endTime])
                          ->orWhere(function($q) use ($startTime, $endTime) {
                              $q->where('appointment_datetime_start', '<=', $startTime)
                                ->where('appointment_datetime_end', '>=', $endTime);
                          });
                })
                ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_clinic'])
                ->with('patient:id,name,email')
                ->get();

            // Check blocked time slots
            $blockedSlots = BlockedTimeSlot::where('doctor_user_id', Auth::id())
                ->where(function($query) use ($startTime, $endTime) {
                    $query->where(function($q) use ($startTime, $endTime) {
                        $q->where('start_datetime', '<=', $startTime)
                          ->where('end_datetime', '>', $startTime);
                    })->orWhere(function($q) use ($startTime, $endTime) {
                        $q->where('start_datetime', '<', $endTime)
                          ->where('end_datetime', '>=', $endTime);
                    })->orWhere(function($q) use ($startTime, $endTime) {
                        $q->where('start_datetime', '>=', $startTime)
                          ->where('end_datetime', '<=', $endTime);
                    });
                })
                ->get();

            $hasConflicts = $conflicts->count() > 0 || $blockedSlots->count() > 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'has_conflicts' => $hasConflicts,
                    'appointment_conflicts' => $conflicts,
                    'blocked_slots' => $blockedSlots,
                    'can_book' => !$hasConflicts,
                    'checked_time_slot' => [
                        'start' => $startTime->format('Y-m-d H:i:s'),
                        'end' => $endTime->format('Y-m-d H:i:s'),
                        'duration_minutes' => $startTime->diffInMinutes($endTime)
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check conflicts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get doctor's appointment settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $doctor = Auth::user()->doctor;
            
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'max_patient_appointments' => $doctor->max_patient_appointments,
                    'consultation_fee' => $doctor->consultation_fee,
                    'is_available' => $doctor->is_available,
                    'working_hours' => $doctor->working_hours,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update doctor's appointment settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $doctor = Auth::user()->doctor;
            
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'max_patient_appointments' => 'nullable|integer|min:1|max:50',
                'consultation_fee' => 'nullable|numeric|min:0',
                'is_available' => 'nullable|boolean',
                'working_hours' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $doctor->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => [
                    'max_patient_appointments' => $doctor->max_patient_appointments,
                    'consultation_fee' => $doctor->consultation_fee,
                    'is_available' => $doctor->is_available,
                    'working_hours' => $doctor->working_hours,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reschedule appointment (Doctor)
     */
    public function reschedule(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'new_datetime_start' => 'required|date|after:' . now()->addHours(2),
                'new_datetime_end' => 'nullable|date|after:new_datetime_start',
                'reason' => 'nullable|string|max:500',
                'notes_by_staff' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointment = Appointment::findOrFail($id);
            $doctor = Auth::user()->doctor;

            // Verify doctor owns this appointment
            if ($appointment->doctor_user_id !== $doctor->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reschedule this appointment'
                ], 403);
            }

            $rescheduleData = $validator->validated();
            
            // Set end time if not provided
            if (empty($rescheduleData['new_datetime_end'])) {
                $rescheduleData['new_datetime_end'] = Carbon::parse($rescheduleData['new_datetime_start'])->addMinutes(30);
            }

            $rescheduledAppointment = $this->doctorAppointmentService->rescheduleAppointment(
                Auth::user(), 
                $appointment, 
                $rescheduleData
            );

            return response()->json([
                'success' => true,
                'message' => 'Appointment rescheduled successfully',
                'data' => $rescheduledAppointment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reschedule appointment',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}