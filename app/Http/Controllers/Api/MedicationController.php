<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\Patient;
use App\Services\Medical\PatientMedicalDataService;
use App\Services\Medical\TimelineEventService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MedicationController extends Controller
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
     * Display a listing of medications for a patient.
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
                'status' => $request->query('status'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'limit' => $request->query('limit', 50)
            ];

            $medications = $this->medicalDataService->getPatientMedications($patient->id, $filters);
            
            return $this->success($medications, 'Medications retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve medications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created medication prescription.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'medication_name' => 'required|string|max:255',
                'dosage' => 'required|string|max:100',
                'frequency' => 'required|string|max:100',
                'duration' => 'nullable|string|max:100',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'instructions' => 'nullable|string|max:1000',
                'refills_allowed' => 'nullable|integer|min:0',
                'status' => 'sometimes|in:active,completed,discontinued,on_hold'
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
            
            $medication = Medication::create([
                'patient_id' => $patient->id,
                'doctor_user_id' => $user->id,
                'medication_name' => $request->medication_name,
                'dosage' => $request->dosage,
                'frequency' => $request->frequency,
                'duration' => $request->duration,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'instructions' => $request->instructions,
                'refills_allowed' => $request->refills_allowed ?? 0,
                'status' => $request->status ?? 'active'
            ]);

            // Create timeline event
            $this->timelineEventService->createPrescriptionEvent($medication);

            return $this->success(
                $medication->toFrontendFormat(),
                'Medication prescription created successfully',
                201
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to create medication prescription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified medication.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $medication = Medication::with(['patient.personalInfo', 'prescribedBy'])->findOrFail($id);
            
            // Check permissions
            if (!$user->isPatient() && !$user->hasPermission('patients:view-medical')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $medication->patient_id) {
                return $this->error('Access denied', 403);
            }

            return $this->success(
                $medication->toFrontendFormat(),
                'Medication retrieved successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve medication: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified medication.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'medication_name' => 'sometimes|string|max:255',
                'dosage' => 'sometimes|string|max:100',
                'frequency' => 'sometimes|string|max:100',
                'route' => 'nullable|string|max:50',
                'start_date' => 'sometimes|date',
                'end_date' => 'nullable|date|after:start_date',
                'quantity' => 'nullable|integer|min:1',
                'refills' => 'nullable|integer|min:0',
                'instructions' => 'nullable|string|max:1000',
                'status' => 'sometimes|in:active,completed,discontinued,on_hold',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $medication = Medication::findOrFail($id);
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $medication->update($request->only([
                'medication_name', 'dosage', 'frequency', 'route',
                'start_date', 'end_date', 'quantity', 'refills',
                'instructions', 'status', 'notes'
            ]));

            return $this->success(
                $medication->fresh()->toFrontendFormat(),
                'Medication updated successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to update medication: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified medication.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $medication = Medication::findOrFail($id);
            $medication->delete();

            return $this->success(null, 'Medication deleted successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to delete medication: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Discontinue a medication.
     */
    public function discontinue(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
                'discontinued_date' => 'sometimes|date'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            
            // Check permissions
            if (!$user->hasPermission('patients:manage-medical') && !$user->isDoctor()) {
                return $this->error('Insufficient permissions', 403);
            }

            $medication = Medication::findOrFail($id);
            
            $medication->update([
                'status' => 'discontinued',
                'end_date' => $request->discontinued_date ?? now(),
                'notes' => ($medication->notes ? $medication->notes . "\n\n" : '') . 
                          "Discontinued on " . now()->format('Y-m-d') . ": " . $request->reason
            ]);

            // Create timeline event for discontinuation
            $this->timelineEventService->createMedicationDiscontinuedEvent($medication, $user, $request->reason);

            return $this->success(
                $medication->fresh()->toFrontendFormat(),
                'Medication discontinued successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to discontinue medication: ' . $e->getMessage(), 500);
        }
    }
}
