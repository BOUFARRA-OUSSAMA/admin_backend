<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\PatientRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PatientService
{
    /**
     * @var PatientRepositoryInterface
     */
    protected $patientRepository;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * PatientService constructor.
     * 
     * @param PatientRepositoryInterface $patientRepository
     * @param AuthService $authService
     */
    public function __construct(PatientRepositoryInterface $patientRepository, AuthService $authService)
    {
        $this->patientRepository = $patientRepository;
        $this->authService = $authService;
    }

    /**
     * Get filtered patients with pagination.
     * 
     * @param array $filters
     * @param string $sortBy
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFilteredPatients(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->patientRepository->getFilteredPatients($filters, $sortBy, $sortDirection, $perPage);
    }

    /**
     * Create a new patient.
     * 
     * @param array $data
     * @return User
     */
    public function createPatient(array $data): User
    {
        return $this->patientRepository->createPatient($data);
    }

    /**
     * Get patient by ID.
     * 
     * @param int $patientId
     * @return User
     */
    public function getPatientById(int $patientId): User
    {
        return $this->patientRepository->getPatientById($patientId, ['roles']);
    }

    /**
     * Update patient details.
     * 
     * @param int $patientId
     * @param array $data
     * @return User
     */
    public function updatePatient(int $patientId, array $data): User
    {
        // Get the patient first for logging purposes
        $patient = $this->getPatientById($patientId);

        // Store old values for logging
        $oldValues = $patient->toArray();

        // Update the patient
        $this->patientRepository->updatePatient($patientId, $data);

        // Return the updated patient
        return $this->getPatientById($patientId);
    }

    /**
     * Delete a patient.
     * 
     * @param int $patientId
     * @return bool
     */
    public function deletePatient(int $patientId): bool
    {
        return $this->patientRepository->deletePatient($patientId);
    }

    /**
     * Log patient related activity.
     * 
     * @param int $userId
     * @param string $action
     * @param string $description
     * @param User $patient
     * @param Request $request
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    public function logPatientActivity(
        int $userId,
        string $action,
        string $description,
        User $patient,
        Request $request,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $this->authService->logActivity(
            $userId,
            $action,
            'Patients',
            $description,
            'Patient',
            $patient->id,
            $request->ip(),
            $oldValues,
            $newValues
        );
    }
}
