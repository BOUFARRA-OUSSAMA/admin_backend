<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\Patient;
use App\Services\Medical\PatientMedicalDataService;
use App\Services\Medical\TimelineEventService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LabResultController extends Controller
{
    use ApiResponseTrait;

    protected PatientMedicalDataService $medicalDataService;
    protected TimelineEventService $timelineEventService;

    public function __construct(
        PatientMedicalDataService $medicalDataService,
        TimelineEventService $timelineEventService
    ) {
        $this->medicalDataService = $medicalDataService;
        $this->timelineEventService = $timelineEventService;
    }

    /**
     * Display a listing of lab results for a patient.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get patient ID from request or current user
            $patientId = $request->query('patient_id');
            if (!$patientId && $user->isPatient()) {
                $patient = $user->patient;
                $patientId = $patient ? $patient->id : null;
            }
            
            if (!$patientId) {
                return $this->error('Patient ID is required', 400);
            }

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
            
            return $this->success($labResults, 'Lab results retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve lab results: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created lab result.
     */
    public function store(Request $request): JsonResponse
    {
        try {            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'lab_test_id' => 'nullable|exists:lab_tests,id',
                'test_name' => 'required|string|max:255',
                'result_date' => 'required|date',
                'performed_by_lab_name' => 'nullable|string|max:255',
                'structured_results' => 'nullable|array',
                'interpretation' => 'nullable|string|max:1000',
                'status' => 'required|in:pending,completed,reviewed,cancelled'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }            $patient = Patient::findOrFail($request->patient_id);
              // Handle lab_test_id requirement
            $labTestId = $request->lab_test_id;
            if (!$labTestId) {
                // Try to find existing lab test with matching name
                $existingLabTest = LabTest::where('test_name', $request->test_name)->first();
                
                if ($existingLabTest) {
                    $labTestId = $existingLabTest->id;
                } else {
                    // Create a new lab test for this result
                    $labTest = LabTest::create([
                        'patient_id' => $patient->id,
                        'requested_by_user_id' => $user->id,
                        'test_name' => $request->test_name,
                        'test_code' => strtoupper(substr(str_replace(' ', '', $request->test_name), 0, 10)),
                        'urgency' => 'routine',
                        'requested_date' => now(),
                        'scheduled_date' => $request->result_date,
                        'lab_name' => $request->performed_by_lab_name ?? 'External Lab',
                        'status' => 'completed'
                    ]);
                    $labTestId = $labTest->id;
                }
            }
            
            $labResult = LabResult::create([
                'patient_id' => $patient->id,
                'lab_test_id' => $labTestId,
                'result_date' => $request->result_date,
                'performed_by_lab_name' => $request->performed_by_lab_name,
                'structured_results' => $request->structured_results,
                'interpretation' => $request->interpretation,
                'status' => $request->status,
                'reviewed_by_user_id' => $user->id
            ]);

            // Update lab test status if needed
            if ($labTestId) {
                $labTest = LabTest::find($labTestId);
                if ($labTest && $labTest->status === 'pending') {
                    $labTest->update(['status' => 'completed']);
                }
            }// Create timeline event
            $this->timelineEventService->createLabResultEvent($labResult, $user);

            return $this->success(
                $labResult->toFrontendFormat(),
                'Lab result created successfully',
                201
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to create lab result: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified lab result.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $labResult = LabResult::with([
                'patient.personalInfo', 
                'labTest', 
                'reviewedBy'
            ])->findOrFail($id);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $labResult->patient_id) {
                return $this->error('Access denied', 403);
            }

            return $this->success(
                $labResult->toFrontendFormat(),
                'Lab result retrieved successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve lab result: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified lab result.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_name' => 'sometimes|string|max:255',
                'result_value' => 'sometimes|string|max:500',
                'unit' => 'nullable|string|max:50',
                'reference_range' => 'nullable|string|max:100',
                'status' => 'sometimes|in:normal,abnormal,critical,pending',
                'result_date' => 'sometimes|date',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $labResult = LabResult::findOrFail($id);
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $labResult->update($request->only([
                'test_name', 'result_value', 'unit', 'reference_range',
                'status', 'result_date', 'notes'
            ]));

            return $this->success(
                $labResult->fresh()->toFrontendFormat(),
                'Lab result updated successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to update lab result: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified lab result.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $labResult = LabResult::findOrFail($id);
            $labResult->delete();

            return $this->success(null, 'Lab result deleted successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to delete lab result: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get lab result history for a specific test type.
     */
    public function history(Request $request, string $patientId, string $testName): JsonResponse
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

            $labResults = LabResult::where('patient_id', $patient->id)
                ->where('test_name', 'like', '%' . $testName . '%')
                ->with(['labTest', 'reviewedBy'])
                ->orderBy('result_date', 'desc')
                ->limit($request->query('limit', 20))
                ->get()
                ->map(function ($result) {
                    return $result->toFrontendFormat();
                });

            return $this->success($labResults, 'Lab result history retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve lab result history: ' . $e->getMessage(), 500);
        }
    }
}
