<?php
 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Medical\PatientMedicalDataService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PatientMedicalController extends Controller
{
    use ApiResponseTrait;

    protected PatientMedicalDataService $medicalDataService;

    public function __construct(PatientMedicalDataService $medicalDataService)
    {
        $this->medicalDataService = $medicalDataService;
    }

    /**
     * Centralized method to authorize and retrieve the patient model.
     * Handles both patient-specific routes and staff/doctor routes.
     */
    private function authorizeAndGetPatient(Request $request, ?string $patientId, string $permission = 'patients:view-medical'): JsonResponse|Patient
    {
        $user = Auth::user();

        try {
            // Case 1: Route for the logged-in patient (e.g., /api/patient/medical/summary)
            if (is_null($patientId)) {
                if (!$user->isPatient()) {
                    return $this->error('This endpoint is for patients only.', 403);
                }
                return $user->patient;
            }

            // Case 2: Route for staff/doctor (e.g., /api/patients/{id}/medical/summary)
            $patient = Patient::findOrFail($patientId);

            // A patient can only access their own data, even with a direct URL
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied. You can only view your own data.', 403);
            }

            // A staff/doctor must have the required permission
            if (!$user->isPatient() && !$user->hasPermission($permission)) {
                return $this->error('Insufficient permissions.', 403);
            }

            return $patient;

        } catch (ModelNotFoundException $e) {
            return $this->error('Patient not found.', 404);
        }
    }

    public function summary(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $summary = $patient->getPatientSummary();
            return $this->success($summary, 'Patient medical summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient medical summary: ' . $e->getMessage(), 500);
        }
    }

    public function vitals(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $filters = $request->only(['date_from', 'date_to', 'limit']);
            $vitals = $this->medicalDataService->getPatientVitalSigns($patient->id, $filters);
            return $this->success($vitals, 'Patient vital signs retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient vital signs: ' . $e->getMessage(), 500);
        }
    }

    public function medications(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $filters = $request->only(['status', 'date_from', 'date_to', 'limit']);
            $medications = $this->medicalDataService->getPatientMedications($patient->id, $filters);
            return $this->success($medications, 'Patient medications retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient medications: ' . $e->getMessage(), 500);
        }
    }

    public function labResults(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $filters = $request->only(['test_type', 'status', 'search', 'sortBy', 'date_from', 'date_to', 'limit']);
            $labResults = $this->medicalDataService->getPatientLabResults($patient->id, $filters);
            return $this->success($labResults, 'Patient lab results retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient lab results: ' . $e->getMessage(), 500);
        }
    }

    public function medicalHistory(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $filters = $request->only(['condition_type', 'status', 'limit']);
            $medicalHistory = $this->medicalDataService->getPatientMedicalHistory($patient->id, $filters);
            return $this->success($medicalHistory, 'Patient medical history retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient medical history: ' . $e->getMessage(), 500);
        }
    }

    public function timeline(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $filters = $request->only(['event_type', 'date_from', 'date_to', 'limit']);
            $timeline = $this->medicalDataService->getPatientTimeline($patient->id, $filters);
            return $this->success($timeline, 'Patient timeline retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient timeline: ' . $e->getMessage(), 500);
        }
    }

    public function files(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId, 'patients:view-files');
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $filters = $request->only(['category', 'date_from', 'date_to', 'limit']);
            $files = $this->medicalDataService->getPatientFiles($patient->id, $filters);
            return $this->success($files, 'Patient files retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient files: ' . $e->getMessage(), 500);
        }
    }

    public function notes(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId, 'patients:view-notes');
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $filters = $request->only(['type', 'date_from', 'date_to', 'limit']);
            $filters['include_private'] = Auth::user()->hasPermission('patients:view-private-notes');
            $notes = $this->medicalDataService->getPatientNotes($patient->id, $filters);
            return $this->success($notes, 'Patient notes retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient notes: ' . $e->getMessage(), 500);
        }
    }

    public function alerts(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $filters = $request->only(['severity', 'status', 'limit']);
            $alerts = $this->medicalDataService->getPatientAlerts($patient->id, $filters);
            return $this->success($alerts, 'Patient alerts retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient alerts: ' . $e->getMessage(), 500);
        }
    }

    public function statistics(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $statistics = $patient->calculatePatientStats();
            return $this->success($statistics, 'Patient statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient statistics: ' . $e->getMessage(), 500);
        }
    }

/**
     * ✅ AJOUTER CETTE NOUVELLE MÉTHODE
     * Get summary statistics for the patient dashboard.
     */
 public function summaryStatistics(Request $request, string $patientId = null): JsonResponse
    {
        $patientOrResponse = $this->authorizeAndGetPatient($request, $patientId);
        if ($patientOrResponse instanceof JsonResponse) return $patientOrResponse;
        $patient = $patientOrResponse;

        try {
            $statistics = $this->medicalDataService->getPatientSummaryStatistics($patient);
            return $this->success($statistics, 'Patient summary statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient summary statistics: ' . $e->getMessage(), 500);
        }
    }
 

}

