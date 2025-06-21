<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Medical\MedicalHistoryRequest;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Services\MedicalHistoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicalHistoryController extends Controller
{
    use ApiResponseTrait;

    protected MedicalHistoryService $medicalHistoryService;

    public function __construct(MedicalHistoryService $medicalHistoryService)
    {
        $this->medicalHistoryService = $medicalHistoryService;
    }

    /**
     * Display a listing of medical histories for a patient.
     */
    public function index(Request $request, string $patient): JsonResponse
    {
        try {
            $patientId = $patient; // Get from route parameter instead of query
            
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);

            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }

            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $medicalHistories = $this->medicalHistoryService->getPatientMedicalHistories($patientId);

            return $this->success($medicalHistories, 'Medical histories retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve medical histories: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created medical history.
     */
    public function store(MedicalHistoryRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $patientId = $request->input('patient_id');
            $patient = Patient::findOrFail($patientId);

            // Check permissions - only medical staff can create medical history
            if (!$user->hasPermission('patients:edit-medical')) {
                return $this->error('Only medical staff can create medical history records', 403);
            }

            $data = $request->validated();
            $data['updated_by_user_id'] = $user->id;

            $medicalHistory = $this->medicalHistoryService->createMedicalHistory($patientId, $data);

            return $this->success(
                $medicalHistory->toFrontendFormat(),
                'Medical history created successfully',
                201
            );

        } catch (\Exception $e) {
            return $this->error('Failed to create medical history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified medical history.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $medicalHistory = MedicalHistory::findOrFail($id);
            $patient = $medicalHistory->patient;

            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }

            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            return $this->success(
                $medicalHistory->toFrontendFormat(),
                'Medical history retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve medical history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified medical history.
     */
    public function update(MedicalHistoryRequest $request, string $patient, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $medicalHistory = MedicalHistory::findOrFail($id); // Use $id, not $patient
            $patientModel = $medicalHistory->patient;

            // Check permissions - only medical staff can update medical history
            if (!$user->hasPermission('patients:edit-medical')) {
                return $this->error('Only medical staff can update medical history records', 403);
            }

            $data = $request->validated();
            $data['updated_by_user_id'] = $user->id;

            $updatedMedicalHistory = $this->medicalHistoryService->updateMedicalHistory($id, $data); // Use $id

            return $this->success(
                $updatedMedicalHistory->toFrontendFormat(),
                'Medical history updated successfully'
            );

        } catch (\Exception $e) {
            return $this->error('Failed to update medical history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete medical history
     */
    public function destroy(Request $request, int $patientId, int $historyId): JsonResponse
    {
        try {
            // Validate that the patient exists
            $patient = Patient::findOrFail($patientId);
            
            // Validate that the medical history exists and belongs to the patient
            $medicalHistory = MedicalHistory::where('id', $historyId)
                ->where('patient_id', $patientId)
                ->firstOrFail();
            
            // Get the current user ID
            $userId = $request->user()->id ?? 1; // Fallback to user ID 1 if not available
            
            // Delete the medical history
            $result = $this->medicalHistoryService->deleteMedicalHistory($historyId, $userId);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Medical history deleted successfully',
                    'data' => null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete medical history',
                    'data' => null
                ], 500);
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medical history record not found',
                'data' => null
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Error in MedicalHistoryController@destroy', [
                'error' => $e->getMessage(),
                'patient_id' => $patientId,
                'history_id' => $historyId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred',
                'data' => null
            ], 500);
        }
    }
}