<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface PatientRepositoryInterface
{
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
    ): LengthAwarePaginator;

    /**
     * Create a new patient.
     * 
     * @param array $data
     * @return User
     */
    public function createPatient(array $data): User;

    /**
     * Get patient by ID.
     * 
     * @param int $patientId
     * @param array $relations
     * @return User
     */
    public function getPatientById(int $patientId, array $relations = []): User;

    /**
     * Update patient details.
     * 
     * @param int $patientId
     * @param array $data
     * @return bool
     */
    public function updatePatient(int $patientId, array $data): bool;

    /**
     * Delete a patient.
     * 
     * @param int $patientId
     * @return bool
     */
    public function deletePatient(int $patientId): bool;
}
