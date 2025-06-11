<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Services\Medical\TimelineEventService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class PatientAlertController extends Controller
{
    protected TimelineEventService $timelineEventService;

    public function __construct(TimelineEventService $timelineEventService)
    {
        $this->timelineEventService = $timelineEventService;
    }

    /**
     * Display a listing of patient alerts.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'patient_id' => 'nullable|exists:patients,id',
                'alert_type' => 'nullable|in:allergy,medication,condition,warning',
                'severity' => 'nullable|in:low,medium,high,critical',
                'is_active' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = PatientAlert::query()->with('patient.personalInfo');

            // Role-based filtering
            if ($user->isPatient()) {
                // Patients can only see their own alerts
                $query->whereHas('patient', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->isDoctor()) {
                // Doctors can see alerts for their patients
                if ($request->patient_id) {
                    $query->where('patient_id', $request->patient_id);
                } else {
                    // If no specific patient, show alerts for doctor's patients
                    $query->whereHas('patient.appointments', function($q) use ($user) {
                        $q->where('doctor_user_id', $user->id);
                    });
                }
            }
            // Admin/staff can see all alerts (no additional filtering)

            // Apply filters
            if ($request->patient_id) {
                $query->where('patient_id', $request->patient_id);
            }

            if ($request->alert_type) {
                $query->where('alert_type', $request->alert_type);
            }

            if ($request->severity) {
                $query->where('severity', $request->severity);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Order by severity and creation date
            $query->orderByRaw("CASE 
                WHEN severity = 'critical' THEN 1 
                WHEN severity = 'high' THEN 2 
                WHEN severity = 'medium' THEN 3 
                WHEN severity = 'low' THEN 4 
                ELSE 5 
            END")->orderBy('created_at', 'desc');

            $perPage = $request->get('per_page', 15);
            $alerts = $query->paginate($perPage);

            $transformedAlerts = $alerts->getCollection()->map(function($alert) {
                return $alert->toFrontendFormat();
            });

            $alerts->setCollection($transformedAlerts);

            return response()->json([
                'success' => true,
                'data' => $alerts->items(),
                'pagination' => [
                    'current_page' => $alerts->currentPage(),
                    'last_page' => $alerts->lastPage(),
                    'per_page' => $alerts->perPage(),
                    'total' => $alerts->total(),
                ],
                'message' => 'Patient alerts retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patient alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created patient alert.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'alert_type' => 'required|in:allergy,medication,condition,warning',
                'severity' => 'required|in:low,medium,high,critical',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            $patient = Patient::findOrFail($validatedData['patient_id']);

            // Authorization check
            if ($user->isPatient() && $patient->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only create alerts for yourself'
                ], 403);
            }

            if ($user->isDoctor()) {
                // Check if doctor has relationship with patient
                $hasRelationship = $patient->appointments()
                    ->where('doctor_user_id', $user->id)
                    ->exists();
                    
                if (!$hasRelationship) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only create alerts for your patients'
                    ], 403);
                }
            }

            // Create the alert
            $alert = PatientAlert::create([
                'patient_id' => $validatedData['patient_id'],
                'alert_type' => $validatedData['alert_type'],
                'severity' => $validatedData['severity'],
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'is_active' => $validatedData['is_active'] ?? true,
            ]);

            // Create timeline event
            $this->timelineEventService->createAlertEvent($alert, $user);

            return response()->json([
                'success' => true,
                'data' => $alert->toFrontendFormat(),
                'message' => 'Patient alert created successfully'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create patient alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified patient alert.
     */
    public function show(PatientAlert $patientAlert): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = $patientAlert->patient;

            // Authorization check
            if ($user->isPatient() && $patient->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view your own alerts'
                ], 403);
            }

            if ($user->isDoctor()) {
                // Check if doctor has relationship with patient
                $hasRelationship = $patient->appointments()
                    ->where('doctor_user_id', $user->id)
                    ->exists();
                    
                if (!$hasRelationship) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only view alerts for your patients'
                    ], 403);
                }
            }

            $patientAlert->load('patient.personalInfo');

            return response()->json([
                'success' => true,
                'data' => $patientAlert->toFrontendFormat(),
                'message' => 'Patient alert retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patient alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified patient alert.
     */
    public function update(Request $request, PatientAlert $patientAlert): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = $patientAlert->patient;

            $validator = Validator::make($request->all(), [
                'alert_type' => 'nullable|in:allergy,medication,condition,warning',
                'severity' => 'nullable|in:low,medium,high,critical',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Authorization check
            if ($user->isPatient() && $patient->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update your own alerts'
                ], 403);
            }

            if ($user->isDoctor()) {
                // Check if doctor has relationship with patient
                $hasRelationship = $patient->appointments()
                    ->where('doctor_user_id', $user->id)
                    ->exists();
                    
                if (!$hasRelationship) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only update alerts for your patients'
                    ], 403);
                }
            }

            $validatedData = $validator->validated();
            $oldData = $patientAlert->toArray();

            $patientAlert->update($validatedData);

            // Create timeline event if significant changes
            if (isset($validatedData['severity']) && $validatedData['severity'] !== $oldData['severity']) {
                $this->timelineEventService->createAlertEvent($patientAlert, $user, 'Alert severity updated');
            } elseif (isset($validatedData['is_active']) && $validatedData['is_active'] !== $oldData['is_active']) {
                $action = $validatedData['is_active'] ? 'activated' : 'deactivated';
                $this->timelineEventService->createAlertEvent($patientAlert, $user, "Alert {$action}");
            }

            return response()->json([
                'success' => true,
                'data' => $patientAlert->fresh()->toFrontendFormat(),
                'message' => 'Patient alert updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update patient alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified patient alert.
     */
    public function destroy(PatientAlert $patientAlert): JsonResponse
    {
        try {
            $user = Auth::user();
            $patient = $patientAlert->patient;

            // Authorization check
            if ($user->isPatient() && $patient->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete your own alerts'
                ], 403);
            }

            if ($user->isDoctor()) {
                // Check if doctor has relationship with patient
                $hasRelationship = $patient->appointments()
                    ->where('doctor_user_id', $user->id)
                    ->exists();
                    
                if (!$hasRelationship) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only delete alerts for your patients'
                    ], 403);
                }
            }

            // Create timeline event before deletion
            $this->timelineEventService->createAlertEvent($patientAlert, $user, 'Alert removed');

            $patientAlert->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patient alert deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete patient alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
