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
 * Remove the specified medical history.
 */
public function destroy(string $id): JsonResponse
{
    try {
        $user = Auth::user();
        $medicalHistory = MedicalHistory::findOrFail($id);

        // Check permissions - only medical staff with delete permission
        // if (!$user->hasPermission('patients:delete-medical')) {
        //     return $this->error('Insufficient permissions to delete medical history', 403);
        // }

        // Pass the user ID to the delete method
        $this->medicalHistoryService->deleteMedicalHistory($id, $user->id);

        return $this->success(null, 'Medical history deleted successfully');

    } catch (\Exception $e) {
        return $this->error('Failed to delete medical history: ' . $e->getMessage(), 500);
    }
}
}