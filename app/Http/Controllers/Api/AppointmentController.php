<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppointmentService;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class AppointmentController extends Controller
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * Get all appointments with filters (Receptionist/Admin)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'patient_id' => $request->get('patient_id'),
                'doctor_id' => $request->get('doctor_id'),
                'status' => $request->get('status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'date' => $request->get('date'),
                'search' => $request->get('search'),
                'upcoming' => $request->boolean('upcoming'),
                'today' => $request->boolean('today'),
                'this_week' => $request->boolean('this_week'),
            ];

            $perPage = $request->get('per_page', 15);
            $appointments = $this->appointmentService->getAppointments($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $appointments->items(),
                'pagination' => [
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'from' => $appointments->firstItem(),
                    'to' => $appointments->lastItem(),
                ],
                'filters_applied' => array_filter($filters),
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
     * Create new appointment
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:users,id',
                'doctor_id' => 'required|exists:users,id',
                'appointment_datetime_start' => 'required|date|after:now',
                'appointment_datetime_end' => 'nullable|date|after:appointment_datetime_start',
                'type' => 'nullable|string|max:50',
                'reason' => 'required|string|max:500',
                'patient_notes' => 'nullable|string|max:1000',
                'staff_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointmentData = $validator->validated();
            
            // Set end time if not provided (default 30 minutes)
            if (empty($appointmentData['appointment_datetime_end'])) {
                $appointmentData['appointment_datetime_end'] = Carbon::parse($appointmentData['appointment_datetime_start'])->addMinutes(30);
            }

            $appointment = $this->appointmentService->createAppointment($appointmentData, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Appointment created successfully',
                'data' => $appointment
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
     * Get specific appointment
     */
    public function show($id): JsonResponse
    {
        try {
            // ✅ REMOVED: timeSlot from relationships
            $appointment = Appointment::with(['patient', 'doctor'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $appointment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update appointment
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'appointment_datetime_start' => 'sometimes|date',
                'appointment_datetime_end' => 'sometimes|date|after:appointment_datetime_start',
                'type' => 'sometimes|string|max:50',
                'reason' => 'sometimes|string|max:500',
                'status' => 'sometimes|in:' . implode(',', Appointment::getStatuses()),
                'patient_notes' => 'nullable|string|max:1000',
                'staff_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedAppointment = $this->appointmentService->updateAppointment(
                $appointment, 
                $validator->validated(), 
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Appointment updated successfully',
                'data' => $updatedAppointment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update appointment',
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
                    'message' => 'Cancellation reason is required',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $this->appointmentService->cancelAppointment(
                $appointment, 
                $request->reason, 
                Auth::user()
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment cancelled successfully'
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
     * Confirm appointment
     */
    public function confirm($id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);

            $success = $this->appointmentService->confirmAppointment($appointment, Auth::user());

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment confirmed successfully'
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
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $this->appointmentService->completeAppointment(
                $appointment, 
                Auth::user(), 
                $request->notes
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment completed successfully'
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
     * Get available time slots for a doctor (simplified version)
     */
    public function availableSlots(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:users,id',
                'date' => 'required|date|after_or_equal:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $slots = $this->appointmentService->getAvailableSlots(
                $request->doctor_id,
                Carbon::parse($request->date)
            );

            return response()->json([
                'success' => true,
                'data' => $slots,
                'date' => $request->date,
                'doctor_id' => $request->doctor_id
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available slots',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete appointment (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);

            // Only allow deletion of cancelled or completed appointments
            if (!in_array($appointment->status, [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC,
                Appointment::STATUS_COMPLETED
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only cancelled or completed appointments can be deleted'
                ], 400);
            }

            $appointment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Appointment deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete appointment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reschedule appointment (Admin/Receptionist)
     */
    public function reschedule(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'new_datetime_start' => 'required|date|after:now',
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
            $rescheduleData = $validator->validated();
            
            // Set end time if not provided
            if (empty($rescheduleData['new_datetime_end'])) {
                $rescheduleData['new_datetime_end'] = Carbon::parse($rescheduleData['new_datetime_start'])->addMinutes(30);
            }

            $rescheduledAppointment = $this->appointmentService->rescheduleAppointment(
                Auth::user(), 
                $appointment, 
                $rescheduleData
            );

            return response()->json([
                'success' => true,
                'message' => 'Appointment rescheduled successfully by staff',
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