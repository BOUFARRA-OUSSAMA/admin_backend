<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\User;
use App\Models\Patient;
use App\Models\PersonalInfo;
use App\Services\PatientService;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\PatientResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ReceptionistPatientController extends Controller
{
    use ApiResponseTrait;

    protected PatientService $patientService;
    protected AuthService $authService;

    public function __construct(PatientService $patientService, AuthService $authService)
    {
        $this->patientService = $patientService;
        $this->authService = $authService;
    }

    /**
     * Get patients with enhanced data for receptionist interface
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

        // Transform data for receptionist interface
        $transformedData = $patients->getCollection()->map(function ($patient) {
            return $this->transformPatientForReceptionist($patient);
        });

        return response()->json([
            'success' => true,
            'message' => 'Patients retrieved successfully',
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $patients->currentPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
                'last_page' => $patients->lastPage(),
                'from' => $patients->firstItem(),
                'to' => $patients->lastItem(),
            ]
        ]);
    }

    /**
     * Store a new patient with personal info
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:Homme,Femme,Autre,male,female,other',
            'nationality' => 'nullable|string|max:100',
            'blood_group' => 'nullable|string|max:10',
            'marital_status' => 'nullable|in:Célibataire,Marié(e),Divorcé(e),Veuf/Veuve,Autre,single,married,divorced,widowed,other',
            'address' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,pending',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            DB::beginTransaction();

            // Create user with patient role
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('password123'), // Default password
                'phone' => $request->phone,
                'status' => $request->status ?? 'active',
            ];

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('patients/photos', 'public');
                $userData['profile_image'] = $photoPath;
            }

            $patient = $this->patientService->createPatient($userData);

            // Create patient record
            $patientRecord = Patient::create([
                'user_id' => $patient->id,
                'registration_date' => now()
            ]);

            // Create personal info with converted values
            $personalInfoData = [
                'patient_id' => $patientRecord->id,
                'name' => explode(' ', $request->name)[0] ?? $request->name,
                'surname' => implode(' ', array_slice(explode(' ', $request->name), 1)) ?: '',
                'birthdate' => $request->dob,
                'gender' => $this->convertGenderToDatabase($request->gender),
                'nationality' => $request->nationality,
                'blood_type' => $request->blood_group,
                'marital_status' => $this->convertMaritalStatusToDatabase($request->marital_status),
                'address' => $request->address,
            ];

            PersonalInfo::create($personalInfoData);

            // Log activity
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->patientService->logPatientActivity(
                $authUser->id,
                'create',
                'Created patient: ' . $patient->name,
                $patient,
                $request
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Patient created successfully',
                'data' => $this->transformPatientForReceptionist($patient->load('patient.personalInfo'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update patient with personal info
     */
    public function update(Request $request, User $patient)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $patient->id,
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:Homme,Femme,Autre,male,female,other',
            'nationality' => 'nullable|string|max:100',
            'blood_group' => 'nullable|string|max:10',
            'marital_status' => 'nullable|in:Célibataire,Marié(e),Divorcé(e),Veuf/Veuve,Autre,single,married,divorced,widowed,other',
            'address' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,pending',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            DB::beginTransaction();

            // Update user data
            $userData = [];
            if ($request->has('name')) $userData['name'] = $request->name;
            if ($request->has('email')) $userData['email'] = $request->email;
            if ($request->has('phone')) $userData['phone'] = $request->phone;
            if ($request->has('status')) $userData['status'] = $request->status;

            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo
                if ($patient->profile_image) {
                    Storage::disk('public')->delete($patient->profile_image);
                }
                $photoPath = $request->file('photo')->store('patients/photos', 'public');
                $userData['profile_image'] = $photoPath;
            }

            if (!empty($userData)) {
                $patient->update($userData);
            }

            // Update personal info
            $patientRecord = $patient->patient;
            if ($patientRecord && $patientRecord->personalInfo) {
                $personalInfoData = [];
                
                if ($request->has('name')) {
                    $personalInfoData['name'] = explode(' ', $request->name)[0] ?? $request->name;
                    $personalInfoData['surname'] = implode(' ', array_slice(explode(' ', $request->name), 1)) ?: '';
                }
                if ($request->has('dob')) $personalInfoData['birthdate'] = $request->dob;
                if ($request->has('gender')) $personalInfoData['gender'] = $this->convertGenderToDatabase($request->gender);
                if ($request->has('nationality')) $personalInfoData['nationality'] = $request->nationality;
                if ($request->has('blood_group')) $personalInfoData['blood_type'] = $request->blood_group;
                if ($request->has('marital_status')) $personalInfoData['marital_status'] = $this->convertMaritalStatusToDatabase($request->marital_status);
                if ($request->has('address')) $personalInfoData['address'] = $request->address;

                if (!empty($personalInfoData)) {
                    $patientRecord->personalInfo->update($personalInfoData);
                }
            }

            // Log activity
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->patientService->logPatientActivity(
                $authUser->id,
                'update',
                'Updated patient: ' . $patient->name,
                $patient,
                $request
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Patient updated successfully',
                'data' => $this->transformPatientForReceptionist($patient->fresh()->load('patient.personalInfo'))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete patient
     */
    public function destroy(User $patient, Request $request)
    {
        try {
            $patientName = $patient->name;
            $result = $this->patientService->deletePatient($patient->id);

            if (!$result) {
                return $this->error('Failed to delete patient', 500);
            }

            // Log activity
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->patientService->logPatientActivity(
                $authUser->id,
                'delete',
                'Deleted patient: ' . $patientName,
                $patient,
                $request
            );

            return response()->json([
                'success' => true,
                'message' => 'Patient deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to delete patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform patient data for receptionist interface
     */
    private function transformPatientForReceptionist(User $patient): array
    {
        $personalInfo = $patient->patient?->personalInfo;

        return [
            'id' => $patient->id,
            'name' => $patient->name,
            'email' => $patient->email,
            'phone' => $patient->phone ?? 'Non renseigné',
            'status' => $patient->status,
            'photo' => $patient->profile_image ? Storage::url($patient->profile_image) : null,
            
            // Personal info with conversion back to French
            'dob' => $personalInfo?->birthdate,
            'gender' => $this->convertGenderFromDatabase($personalInfo?->gender),
            'nationality' => $personalInfo?->nationality ?? 'Non renseignée',
            'blood_group' => $personalInfo?->blood_type ?? 'Non renseigné',
            'marital_status' => $this->convertMaritalStatusFromDatabase($personalInfo?->marital_status) ?? 'Non renseigné',
            'address' => $personalInfo?->address ?? 'Non renseignée',
            'emergency_contact' => $personalInfo?->emergency_contact,

            'created_at' => $patient->created_at->toIso8601String(),
            'updated_at' => $patient->updated_at->toIso8601String(),
        ];
    }

    /**
     * Convert gender from French to database format
     */
    private function convertGenderToDatabase(?string $gender): ?string
    {
        if (!$gender) return null;

        $mapping = [
            'Homme' => 'male',
            'Femme' => 'female',
            'Autre' => 'other',
            'male' => 'male',
            'female' => 'female',
            'other' => 'other'
        ];

        return $mapping[$gender] ?? null;
    }

    /**
     * Convert gender from database to French format for display
     */
    private function convertGenderFromDatabase(?string $gender): ?string
    {
        if (!$gender) return null;

        $mapping = [
            'male' => 'Homme',
            'female' => 'Femme',
            'other' => 'Autre'
        ];

        return $mapping[$gender] ?? $gender;
    }

    /**
     * Convert marital status from French to database format
     */
    private function convertMaritalStatusToDatabase(?string $maritalStatus): ?string
    {
        if (!$maritalStatus) return null;

        $mapping = [
            'Célibataire' => 'single',
            'Marié(e)' => 'married',
            'Divorcé(e)' => 'divorced',
            'Veuf/Veuve' => 'widowed',
            'Autre' => 'other',
            'single' => 'single',
            'married' => 'married',
            'divorced' => 'divorced',
            'widowed' => 'widowed',
            'other' => 'other'
        ];

        return $mapping[$maritalStatus] ?? null;
    }

    /**
     * Convert marital status from database to French format for display
     */
    private function convertMaritalStatusFromDatabase(?string $maritalStatus): ?string
    {
        if (!$maritalStatus) return null;

        $mapping = [
            'single' => 'Célibataire',
            'married' => 'Marié(e)',
            'divorced' => 'Divorcé(e)',
            'widowed' => 'Veuf/Veuve',
            'other' => 'Autre'
        ];

        return $mapping[$maritalStatus] ?? $maritalStatus;
    }
}