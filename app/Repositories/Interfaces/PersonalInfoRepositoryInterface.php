<?php

namespace App\Repositories\Interfaces;

use App\Models\PersonalInfo;

interface PersonalInfoRepositoryInterface
{
    /**
     * Get personal info by patient ID.
     *
     * @param int $patientId
     * @return PersonalInfo|null
     */
    public function getByPatientId(int $patientId): ?PersonalInfo;

    /**
     * Create new personal info record.
     *
     * @param array $data
     * @return PersonalInfo
     */
    public function create(array $data): PersonalInfo;

    /**
     * Update personal info record.
     *
     * @param PersonalInfo $personalInfo
     * @param array $data
     * @return PersonalInfo
     */
    public function update(PersonalInfo $personalInfo, array $data): PersonalInfo;

    /**
     * Update or create personal info by patient ID.
     *
     * @param int $patientId
     * @param array $data
     * @return PersonalInfo
     */
    public function updateOrCreateByPatientId(int $patientId, array $data): PersonalInfo;

    /**
     * Delete personal info record.
     *
     * @param PersonalInfo $personalInfo
     * @return bool
     */
    public function delete(PersonalInfo $personalInfo): bool;
}