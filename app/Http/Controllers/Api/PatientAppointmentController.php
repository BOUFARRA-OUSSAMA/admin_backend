<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PatientAppointmentService;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class PatientAppointmentController extends Controller
{
    protected PatientAppointmentService $patientAppointmentService;

    public function __construct(PatientAppointmentService $patientAppointmentService)
    {
        $this->patientAppointmentService = $patientAppointmentService;
    }

    /**
     * Get patient's appointments
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
            $appointments = $this->patientAppointmentService->getMyAppointments(Auth::user(), $filters, $perPage);

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
     * Get upcoming appointments
     */
    public function upcoming(): JsonResponse
    {
        try {
            $appointments = $this->patientAppointmentService->getUpcomingAppointments(Auth::user());

            return response()->json([
                'success' => true,
                'data' => $appointments
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
     * Get today's appointments
     */
    public function today(): JsonResponse
    {
        try {
            $appointments = $this->patientAppointmentService->getTodaysAppointments(Auth::user());

            return response()->json([
                'success' => true,
                'data' => $appointments
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve today\'s appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get next appointment
     */
    public function next(): JsonResponse
    {
        try {
            $appointment = $this->patientAppointmentService->getNextAppointment(Auth::user());

            return response()->json([
                'success' => true,
                'data' => $appointment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve next appointment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get appointment history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $history = $this->patientAppointmentService->getAppointmentHistory(Auth::user(), $perPage);

            return response()->json([
                'success' => true,
                'data' => $history->items(),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                ],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve appointment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Book new appointment
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:users,id',
                'appointment_datetime_start' => 'required|date|after:' . now()->addHours(2),
                'appointment_datetime_end' => 'nullable|date|after:appointment_datetime_start',
                'type' => 'nullable|string|max:50',
                'reason' => 'required|string|max:500',
                'notes_by_patient' => 'nullable|string|max:1000', // ✅ CHANGED from 'patient_notes'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $appointmentData = $validator->validated();
            
            // ✅ ADD: Map doctor_id to doctor_user_id for consistency
            $appointmentData['doctor_user_id'] = $appointmentData['doctor_id'];
            unset($appointmentData['doctor_id']); // Remove to avoid confusion
            
            // ✅ ADD: Map reason to reason_for_visit (if your Appointment model expects this)
            $appointmentData['reason_for_visit'] = $appointmentData['reason'];
            // Keep 'reason' as well for compatibility
            
            // Set end time if not provided
            if (empty($appointmentData['appointment_datetime_end'])) {
                $appointmentData['appointment_datetime_end'] = Carbon::parse($appointmentData['appointment_datetime_start'])->addMinutes(30);
            }

            $appointment = $this->patientAppointmentService->bookAppointment(Auth::user(), $appointmentData);

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully',
                'data' => $appointment
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to book appointment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel patient's appointment
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

            $success = $this->patientAppointmentService->cancelMyAppointment(
                Auth::user(), 
                $appointment, 
                $request->reason
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
     * Reschedule patient's appointment
     */
    public function reschedule(Request $request, $id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'appointment_datetime_start' => 'required|date|after:' . now()->addHours(2),
                'appointment_datetime_end' => 'nullable|date|after:appointment_datetime_start',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $newTimeData = $validator->validated();
            
            if (empty($newTimeData['appointment_datetime_end'])) {
                $newTimeData['appointment_datetime_end'] = Carbon::parse($newTimeData['appointment_datetime_start'])->addMinutes(30);
            }

            $updatedAppointment = $this->patientAppointmentService->rescheduleMyAppointment(
                Auth::user(), 
                $appointment, 
                $newTimeData
            );

            return response()->json([
                'success' => true,
                'message' => 'Appointment rescheduled successfully',
                'data' => $updatedAppointment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reschedule appointment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get available doctors
     */
    public function availableDoctors(): JsonResponse
    {
        try {
            $doctors = $this->patientAppointmentService->getAvailableDoctors();

            return response()->json([
                'success' => true,
                'data' => $doctors
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available doctors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available time slots for a doctor
     */
    public function availableSlots(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:users,id',
                'date' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addMonths(3)->format('Y-m-d')
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $slots = $this->patientAppointmentService->getAvailableSlots(
                $request->doctor_id, // This is actually user_id
                $request->date
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
     * Get patient statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->patientAppointmentService->getPatientStats(Auth::user());

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
}