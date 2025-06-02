<?php
// filepath: c:\Users\Microsoft\Desktop\project\admin_backend\app\Http\Controllers\Api\AppointmentHistoryController.php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Services\GetAppointmentHistoryService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use App\DTOs\AppointmentDTO;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AppointmentHistoryController extends Controller
{
    private GetAppointmentHistoryService $getAppointmentHistoryService;

    public function __construct(GetAppointmentHistoryService $getAppointmentHistoryService)
    {
        $this->getAppointmentHistoryService = $getAppointmentHistoryService;
        Log::info("AppointmentHistoryController initialized");
    }

    /**
     * Récupère l'historique des rendez-vous pour le patient authentifié.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = JWTAuth::parseToken()->authenticate();
            Log::info("User authenticated", ['user_id' => $user->id]);
            
            // Trouver le patient associé à l'utilisateur authentifié
            $patient = Patient::where('user_id', $user->id)->first();
            
            if (!$patient) {
                Log::warning("No patient record found for user", ['user_id' => $user->id]);
                return response()->json(['error' => 'No patient record found for this user.'], 404);
            }
            
            Log::info("Patient found", ['patient_id' => $patient->id]);
            
            // Utiliser l'ID du patient authentifié
            $appointmentDTOs = $this->getAppointmentHistoryService->execute($patient->id);
            
            return response()->json($appointmentDTOs);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token absent'], 401);
        } catch (\Exception $e) {
            Log::error("Error in appointment history: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An error occurred while retrieving appointment history.'], 500);
        }
    }

    /**
     * Récupère tous les rendez-vous du patient authentifié avec filtres optionnels.
     */
    public function all(Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = JWTAuth::parseToken()->authenticate();
            
            // Trouver le patient associé à l'utilisateur authentifié
            $patient = Patient::where('user_id', $user->id)->first();
            
            if (!$patient) {
                Log::warning("No patient record found for user", ['user_id' => $user->id]);
                return response()->json(['error' => 'No patient record found for this user.'], 404);
            }
            
            // Récupérer les filtres de la requête
            $type = $request->query('type');
            $status = $request->query('status');
            
            // Récupérer tous les rendez-vous
            $appointments = Appointment::where('patient_user_id', $user->id)
                ->with(['doctor.doctorProfile', 'timeSlot']);
            
            // Appliquer les filtres si présents
            if ($type) {
                $appointments->where('type', $type);
            }
            
            if ($status) {
                $appointments->where('status', $status);
            }
            
            // Exécuter la requête et trier par date
            $appointmentsData = $appointments->orderBy('appointment_datetime_start', 'desc')->get();
            
            // Transformer les données
            $result = $appointmentsData->map(function ($appointment) {
                // Format date
                $formattedDate = Carbon::parse($appointment->appointment_datetime_start)->format('F jS, Y');
                
                // Format time
                $formattedTime = Carbon::parse($appointment->appointment_datetime_start)->format('H:i') .
                             ' - ' .
                             Carbon::parse($appointment->appointment_datetime_end)->format('H:i');
                
                // Format status
                $statusDisplay = ucfirst(str_replace('_', ' ', $appointment->status));
                
                // Get doctor info
                $doctorName = 'N/A';
                $doctorSpecialty = 'N/A';
                
                if ($appointment->doctor) {
                    $doctorName = $appointment->doctor->name;
                    
                    if ($appointment->doctor->doctorProfile) {
                        $doctorSpecialty = $appointment->doctor->doctorProfile->specialty;
                    }
                }
                
                // Get appointment type
                $appointmentType = $appointment->type ?: 'Regular';
                
                return new AppointmentDTO(
                    id: $appointment->id,
                    date: $formattedDate,
                    time: $formattedTime,
                    reason: $appointment->reason_for_visit,
                    status: $statusDisplay,
                    doctorName: $doctorName,
                    doctorSpecialty: $doctorSpecialty,
                    cancelReason: $appointment->cancellation_reason,
                    // location: 'Medical Center',
                    followUp: $appointment->type === 'follow-up',
                    notes: $appointment->notes_by_patient ? [$appointment->notes_by_patient] : [],
                    type: $appointmentType
                );
            });
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Error retrieving filtered appointments: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An error occurred while retrieving appointments.'], 500);
        }
    }

    /**
     * Récupère les détails d'un rendez-vous spécifique.
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = JWTAuth::parseToken()->authenticate();
            
            // Trouver le patient associé à l'utilisateur authentifié
            $patient = Patient::where('user_id', $user->id)->first();
            
            if (!$patient) {
                return response()->json(['error' => 'No patient record found for this user.'], 404);
            }
            
            $appointment = Appointment::with(['doctor.doctorProfile', 'timeSlot'])->find($id);
            
            if (!$appointment) {
                return response()->json(['error' => 'Appointment not found'], 404);
            }
            
            // Vérifier que le rendez-vous appartient au patient authentifié
            if ($appointment->patient_user_id !== $user->id) {
                return response()->json(['error' => 'You are not authorized to view this appointment'], 403);
            }
            
            // Format date
            $formattedDate = Carbon::parse($appointment->appointment_datetime_start)->format('F jS, Y');

            // Format time
            $formattedTime = Carbon::parse($appointment->appointment_datetime_start)->format('H:i') .
                         ' - ' .
                         Carbon::parse($appointment->appointment_datetime_end)->format('H:i');
            
            // Format status
            $statusDisplay = ucfirst(str_replace('_', ' ', $appointment->status));
            
            // Get doctor info
            $doctorName = 'N/A';
            $doctorSpecialty = 'General Medicine';
            
            if ($appointment->doctor) {
                $doctorName = $appointment->doctor->name;
                
                if ($appointment->doctor->doctorProfile) {
                    $doctorSpecialty = $appointment->doctor->doctorProfile->specialty;
                }
            }
            
            // Get appointment type
            $appointmentType = $appointment->type ?: 'Regular';
            
            // Create DTO
            $appointmentDTO = new AppointmentDTO(
                id: $appointment->id,
                date: $formattedDate,
                time: $formattedTime,
                reason: $appointment->reason_for_visit,
                status: $statusDisplay,
                doctorName: $doctorName,
                doctorSpecialty: $doctorSpecialty,
                cancelReason: $appointment->cancellation_reason,
                // location: 'Medical Center, Room ' . ($appointment->room_number ?? rand(100, 999)),
                followUp: $appointment->type === 'follow-up',
                notes: $appointment->notes_by_patient ? [$appointment->notes_by_patient] : [],
                type: $appointmentType
            );
            
            return response()->json($appointmentDTO);
        } catch (\Exception $e) {
            Log::error("Error showing appointment: " . $e->getMessage());
            return response()->json(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Annule un rendez-vous existant.
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = JWTAuth::parseToken()->authenticate();
            
            // Trouver le patient associé à l'utilisateur authentifié
            $patient = Patient::where('user_id', $user->id)->first();
            
            if (!$patient) {
                return response()->json(['error' => 'No patient record found for this user.'], 404);
            }
            
            $appointment = Appointment::find($id);
            
            if (!$appointment) {
                return response()->json(['error' => 'Appointment not found'], 404);
            }
            
            // Vérifier que le rendez-vous appartient au patient authentifié
            if ($appointment->patient_user_id !== $user->id) {
                return response()->json(['error' => 'You are not authorized to cancel this appointment'], 403);
            }
            
            // Vérifier si le rendez-vous peut être annulé
            if (in_array($appointment->status, ['completed', 'cancelled_by_patient', 'cancelled_by_clinic', 'no_show'])) {
                return response()->json(
                    ['error' => 'Cannot cancel appointment with status: ' . $appointment->status], 
                    400
                );
            }
            
            // Mise à jour du statut et de la raison d'annulation
            $appointment->status = 'cancelled_by_patient';
            $appointment->cancellation_reason = $request->input('reason', 'Cancelled by patient');
            $appointment->save();
            
            return response()->json(['message' => 'Appointment cancelled successfully']);
        } catch (\Exception $e) {
            Log::error("Error cancelling appointment: " . $e->getMessage());
            return response()->json(['error' => 'An error occurred while cancelling the appointment.'], 500);
        }
    }
}