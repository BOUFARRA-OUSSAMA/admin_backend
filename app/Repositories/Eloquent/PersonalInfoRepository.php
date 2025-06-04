<?php

namespace App\Repositories\Eloquent;

use App\Models\PersonalInfo;
use App\Repositories\Interfaces\PersonalInfoRepositoryInterface;

class PersonalInfoRepository implements PersonalInfoRepositoryInterface
{
    protected PersonalInfo $model;

    public function __construct(PersonalInfo $model)
    {
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function getByPatientId(int $patientId): ?PersonalInfo
    {
        return $this->model->where('patient_id', $patientId)->first();
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): PersonalInfo
    {
        return $this->model->create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(PersonalInfo $personalInfo, array $data): PersonalInfo
    {
        $personalInfo->update($data);
        return $personalInfo->fresh();
    }

    /**
     * @inheritDoc
     */
    public function updateOrCreateByPatientId(int $patientId, array $data): PersonalInfo
    {
        return $this->model->updateOrCreate(
            ['patient_id' => $patientId],
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(PersonalInfo $personalInfo): bool
    {
        return $personalInfo->delete();
    }
}