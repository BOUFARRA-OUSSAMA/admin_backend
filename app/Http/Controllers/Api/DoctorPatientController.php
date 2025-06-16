<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DoctorPatientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DoctorPatientController extends Controller
{
    protected $doctorPatientService;

    public function __construct(DoctorPatientService $doctorPatientService)
    {
        $this->doctorPatientService = $doctorPatientService;
    }

    /**
     * Get doctor's assigned patients
     */
    public function getMyPatients(Request $request): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            $filters = $request->only(['search', 'status', 'limit', 'page']);
            
            $patients = $this->doctorPatientService->getDoctorPatients($doctorId, $filters);
            
            return response()->json([
                'success' => true,
                'data' => $patients,
                'message' => 'Doctor patients retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive patient summary for doctor view
     */
    public function getPatientSummary(Request $request, $patientId): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            
            $summary = $this->doctorPatientService->getPatientSummaryForDoctor($doctorId, $patientId);
            
            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Patient summary retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patient summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get critical alerts for doctor's patients
     */
    public function getCriticalAlerts(Request $request): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            $filters = $request->only(['severity', 'type', 'limit']);
            
            $alerts = $this->doctorPatientService->getCriticalAlertsForDoctor($doctorId, $filters);
            
            return response()->json([
                'success' => true,
                'data' => $alerts,
                'message' => 'Critical alerts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve critical alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get doctor's dashboard statistics
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            
            $stats = $this->doctorPatientService->getDoctorDashboardStats($doctorId);
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Dashboard statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent patient activity for doctor
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            $limit = $request->input('limit', 10);
            
            $activity = $this->doctorPatientService->getRecentPatientActivity($doctorId, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $activity,
                'message' => 'Recent activity retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search patients for doctor
     */
    public function searchPatients(Request $request): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            $query = $request->input('query');
            $limit = $request->input('limit', 20);
            
            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query is required'
                ], 400);
            }
            
            $patients = $this->doctorPatientService->searchDoctorPatients($doctorId, $query, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $patients,
                'message' => 'Patient search completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get gender demographics for doctor's patients
     */
    public function getGenderDemographics(Request $request): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            
            $demographics = $this->doctorPatientService->getGenderDemographics($doctorId);
            
            return response()->json([
                'success' => true,
                'data' => $demographics,
                'message' => 'Gender demographics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve gender demographics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get age demographics for doctor's patients
     */
    public function getAgeDemographics(Request $request): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            
            $demographics = $this->doctorPatientService->getAgeDemographics($doctorId);
            
            return response()->json([
                'success' => true,
                'data' => $demographics,
                'message' => 'Age demographics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve age demographics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get complete demographics overview for doctor's patients
     */
    public function getDemographicsOverview(Request $request): JsonResponse
    {
        try {
            $doctorId = auth()->id();
            
            $overview = $this->doctorPatientService->getDemographicsOverview($doctorId);
            
            return response()->json([
                'success' => true,
                'data' => $overview,
                'message' => 'Demographics overview retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve demographics overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
