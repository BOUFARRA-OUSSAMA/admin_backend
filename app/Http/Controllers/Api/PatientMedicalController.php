<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Medical\PatientMedicalDataService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PatientMedicalController extends Controller
{
    use ApiResponseTrait;

    protected PatientMedicalDataService $medicalDataService;

    public function __construct(PatientMedicalDataService $medicalDataService)
    {
        $this->medicalDataService = $medicalDataService;
    }

    /**
     * Get comprehensive medical summary for a patient.
     */
    public function summary(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $summary = $patient->getPatientSummary();
            
            return $this->success($summary, 'Patient medical summary retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient medical summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient vital signs.
     */
    public function vitals(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $filters = [
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'limit' => $request->query('limit', 50)
            ];

            $vitals = $this->medicalDataService->getPatientVitalSigns($patient->id, $filters);
            
            return $this->success($vitals, 'Patient vital signs retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient vital signs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient medications.
     */
    public function medications(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $filters = [
                'status' => $request->query('status'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'limit' => $request->query('limit', 50)
            ];

            $medications = $this->medicalDataService->getPatientMedications($patient->id, $filters);
            
            return $this->success($medications, 'Patient medications retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient medications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient lab results.
     */
    public function labResults(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $filters = [
                'test_type' => $request->query('test_type'),
                'status' => $request->query('status'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'limit' => $request->query('limit', 50)
            ];

            $labResults = $this->medicalDataService->getPatientLabResults($patient->id, $filters);
            
            return $this->success($labResults, 'Patient lab results retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient lab results: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient medical history.
     */
    public function medicalHistory(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $filters = [
                'condition_type' => $request->query('condition_type'),
                'status' => $request->query('status'),
                'limit' => $request->query('limit', 50)
            ];

            $medicalHistory = $this->medicalDataService->getPatientMedicalHistory($patient->id, $filters);
            
            return $this->success($medicalHistory, 'Patient medical history retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient medical history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient timeline events.
     */
    public function timeline(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $filters = [
                'event_type' => $request->query('event_type'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'limit' => $request->query('limit', 100)
            ];

            $timeline = $this->medicalDataService->getPatientTimeline($patient->id, $filters);
            
            return $this->success($timeline, 'Patient timeline retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient timeline: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient files.
     */
    public function files(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-files')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $filters = [
                'category' => $request->query('category'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'limit' => $request->query('limit', 50)
            ];

            $files = $this->medicalDataService->getPatientFiles($patient->id, $filters);
            
            return $this->success($files, 'Patient files retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient notes.
     */
    public function notes(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-notes')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $filters = [
                'type' => $request->query('type'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'include_private' => $user->isDoctor() || $user->hasPermission('patients:view-private-notes'),
                'limit' => $request->query('limit', 50)
            ];

            $notes = $this->medicalDataService->getPatientNotes($patient->id, $filters);
            
            return $this->success($notes, 'Patient notes retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient notes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient alerts.
     */
    public function alerts(Request $request, string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $filters = [
                'severity' => $request->query('severity'),
                'status' => $request->query('status'),
                'limit' => $request->query('limit', 50)
            ];

            $alerts = $this->medicalDataService->getPatientAlerts($patient->id, $filters);
            
            return $this->success($alerts, 'Patient alerts retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient alerts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient statistics.
     */
    public function statistics(string $patientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = Patient::findOrFail($patientId);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $statistics = $patient->calculatePatientStats();
            
            return $this->success($statistics, 'Patient statistics retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient statistics: ' . $e->getMessage(), 500);
        }
    }
}
