<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VitalSign;
use App\Models\Patient;
use App\Services\Medical\PatientMedicalDataService;
use App\Services\Medical\TimelineEventService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VitalSignController extends Controller
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
     * Display a listing of vital signs for a patient.
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
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'limit' => $request->query('limit', 50)
            ];

            $vitalSigns = $this->medicalDataService->getPatientVitalSigns($patient->id, $filters);
            
            return $this->success($vitalSigns, 'Vital signs retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve vital signs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created vital sign record.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'recorded_at' => 'sometimes|date',
                'blood_pressure_systolic' => 'nullable|numeric|min:0|max:300',
                'blood_pressure_diastolic' => 'nullable|numeric|min:0|max:200',
                'pulse_rate' => 'nullable|numeric|min:0|max:300',
                'temperature' => 'nullable|numeric|min:30|max:45',
                'temperature_unit' => 'sometimes|string|in:°C,°F,C,F',
                'respiratory_rate' => 'nullable|numeric|min:0|max:100',
                'oxygen_saturation' => 'nullable|numeric|min:0|max:100',
                'weight' => 'nullable|numeric|min:0|max:1000',
                'weight_unit' => 'sometimes|string|in:kg,lbs',
                'height' => 'nullable|numeric|min:0|max:300',
                'height_unit' => 'sometimes|string|in:cm,in,ft',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $patient = Patient::findOrFail($request->patient_id);
            
            $vitalSignData = [
                'patient_id' => $patient->id,
                'blood_pressure_systolic' => $request->blood_pressure_systolic,
                'blood_pressure_diastolic' => $request->blood_pressure_diastolic,
                'pulse_rate' => $request->pulse_rate,
                'temperature' => $request->temperature,
                'respiratory_rate' => $request->respiratory_rate,
                'oxygen_saturation' => $request->oxygen_saturation,
                'weight' => $request->weight,
                'height' => $request->height,
                'notes' => $request->notes,
                'recorded_by_user_id' => $user->id
            ];

            // Only set recorded_at if provided, otherwise use database default
            if ($request->has('recorded_at')) {
                $vitalSignData['recorded_at'] = $request->recorded_at;
            }

            // Only set units if provided, otherwise use database defaults
            if ($request->has('temperature_unit')) {
                $vitalSignData['temperature_unit'] = $request->temperature_unit;
            }
            if ($request->has('weight_unit')) {
                $vitalSignData['weight_unit'] = $request->weight_unit;
            }
            if ($request->has('height_unit')) {
                $vitalSignData['height_unit'] = $request->height_unit;
            }

            $vitalSign = VitalSign::create($vitalSignData);

            // Create timeline event
            $this->timelineEventService->createVitalSignsEvent($vitalSign);

            return $this->success(
                $vitalSign->toFrontendFormat(),
                'Vital signs recorded successfully',
                201
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to record vital signs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified vital sign record.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $vitalSign = VitalSign::with(['patient.personalInfo', 'recordedBy'])->findOrFail($id);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $vitalSign->patient_id) {
                return $this->error('Access denied', 403);
            }

            return $this->success(
                $vitalSign->toFrontendFormat(),
                'Vital sign record retrieved successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve vital sign record: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified vital sign record.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'recorded_at' => 'sometimes|date',
                'blood_pressure_systolic' => 'nullable|numeric|min:0|max:300',
                'blood_pressure_diastolic' => 'nullable|numeric|min:0|max:200',
                'pulse_rate' => 'nullable|numeric|min:0|max:300',
                'temperature' => 'nullable|numeric|min:30|max:45',
                'temperature_unit' => 'nullable|string|in:°C,°F,C,F',
                'respiratory_rate' => 'nullable|numeric|min:0|max:100',
                'oxygen_saturation' => 'nullable|numeric|min:0|max:100',
                'weight' => 'nullable|numeric|min:0|max:1000',
                'weight_unit' => 'nullable|string|in:kg,lbs',
                'height' => 'nullable|numeric|min:0|max:300',
                'height_unit' => 'nullable|string|in:cm,in,ft',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $vitalSign = VitalSign::findOrFail($id);
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $vitalSign->update($request->only([
                'recorded_at', 'blood_pressure_systolic', 'blood_pressure_diastolic', 'pulse_rate',
                'temperature', 'temperature_unit', 'respiratory_rate', 'oxygen_saturation',
                'weight', 'weight_unit', 'height', 'height_unit', 'notes'
            ]));

            return $this->success(
                $vitalSign->fresh()->toFrontendFormat(),
                'Vital sign record updated successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to update vital sign record: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified vital sign record.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $vitalSign = VitalSign::findOrFail($id);
            $vitalSign->delete();

            return $this->success(null, 'Vital sign record deleted successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to delete vital sign record: ' . $e->getMessage(), 500);
        }
    }
}
