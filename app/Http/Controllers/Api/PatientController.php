<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\User;
use App\Services\PatientService;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class PatientController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var PatientService
     */
    protected $patientService;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * PatientController constructor.
     *
     * @param PatientService $patientService
     * @param AuthService $authService
     */
    public function __construct(PatientService $patientService, AuthService $authService)
    {
        $this->patientService = $patientService;
        $this->authService = $authService;
    }

    /**
     * Display a listing of the patients.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ];

        $perPage = $request->query('per_page', 15);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        $patients = $this->patientService->getFilteredPatients($filters, $sortBy, $sortDirection, $perPage);

        return $this->paginated($patients, 'Patients retrieved successfully');
    }

    /**
     * Store a newly created patient in storage.
     *
     * @param  \App\Http\Requests\Patient\StorePatientRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePatientRequest $request)
    {
        try {
            $patient = $this->patientService->createPatient($request->validated());

            // Log the activity
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->patientService->logPatientActivity(
                $authUser->id,
                'create',
                'Created patient: ' . $patient->name,
                $patient,
                $request
            );

            return $this->success($patient, 'Patient created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified patient.
     *
     * @param  \App\Models\User  $patient
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $patient)
    {
        try {
            $patient = $this->patientService->getPatientById($patient->id);

            return $this->success($patient);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient: ' . $e->getMessage(), 404);
        }
    }

    /**
     * Update the specified patient in storage.
     *
     * @param  \App\Http\Requests\Patient\UpdatePatientRequest  $request
     * @param  \App\Models\User  $patient
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePatientRequest $request, User $patient)
    {
        try {
            $oldPatient = $patient->toArray();

            $updatedPatient = $this->patientService->updatePatient($patient->id, $request->validated());

            // Log the activity
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->patientService->logPatientActivity(
                $authUser->id,
                'update',
                'Updated patient: ' . $updatedPatient->name,
                $updatedPatient,
                $request,
                $oldPatient,
                $updatedPatient->toArray()
            );

            return $this->success($updatedPatient, 'Patient updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified patient from storage.
     *
     * @param  \App\Models\User  $patient
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $patient, Request $request)
    {
        try {
            $result = $this->patientService->deletePatient($patient->id);

            if (!$result) {
                return $this->error('Failed to delete patient', 500);
            }

            // Log the activity
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->patientService->logPatientActivity(
                $authUser->id,
                'delete',
                'Deleted patient: ' . $patient->name,
                $patient,
                $request
            );

            return $this->success(null, 'Patient deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get patient medical data summary.
     *
     * @param  \App\Models\User  $patient
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMedicalData(User $patient)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            // Check if user has permission to view patient medical data
            if (!$user->hasPermission('patients:view-medical') && !$user->isPatient()) {
                return $this->error('Insufficient permissions', 403);
            }
            
            // If user is a patient, they can only view their own data
            if ($user->isPatient() && $user->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $patientRecord = $user->isPatient() ? $user->patient : $patient->patient;
            
            if (!$patientRecord) {
                return $this->error('Patient record not found', 404);
            }

            $medicalData = $patientRecord->getPatientSummary();

            return $this->success($medicalData, 'Patient medical data retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient medical data: ' . $e->getMessage(), 500);
        }
    }
}
