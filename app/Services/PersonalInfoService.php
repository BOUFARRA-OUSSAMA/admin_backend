<?php

namespace App\Services;

use App\Http\Resources\PersonalInfoResource;
use App\Models\Patient;
use App\Models\PersonalInfo;
use App\Models\User;
use App\Repositories\Interfaces\PersonalInfoRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Exception;

class PersonalInfoService
{
    protected PersonalInfoRepositoryInterface $personalInfoRepository;

    public function __construct(PersonalInfoRepositoryInterface $personalInfoRepository)
    {
        $this->personalInfoRepository = $personalInfoRepository;
    }

    /**
     * Get personal information for authenticated user.
     *
     * @param User $user
     * @return PersonalInfoResource
     * @throws Exception
     */
    public function getUserPersonalInfo(User $user): PersonalInfoResource
    {
        $patient = $user->patient;
        
        if (!$patient) {
            throw new Exception('Patient record not found for this user');
        }

        $personalInfo = $this->personalInfoRepository->getByPatientId($patient->id);
        
        if (!$personalInfo) {
            // Create empty personal info if it doesn't exist
            $personalInfo = $this->personalInfoRepository->create([
                'patient_id' => $patient->id,
                'name' => explode(' ', $user->name)[0] ?? '',
                'surname' => explode(' ', $user->name)[1] ?? '',
            ]);
        }

        return new PersonalInfoResource($personalInfo->load(['patient.user']));
    }

    /**
     * Update personal information for authenticated user.
     *
     * @param User $user
     * @param array $data
     * @return PersonalInfoResource
     * @throws Exception
     */
    public function updateUserPersonalInfo(User $user, array $data): PersonalInfoResource
    {
        $patient = $user->patient;
        
        if (!$patient) {
            throw new Exception('Patient record not found for this user');
        }

        // Update user email if provided
        if (isset($data['email'])) {
            $user->update(['email' => $data['email']]);
            unset($data['email']); // Remove from personal info data
        }

        $personalInfo = $this->personalInfoRepository->updateOrCreateByPatientId($patient->id, $data);

        return new PersonalInfoResource($personalInfo->load(['patient.user']));
    }

    /**
     * Update profile image for authenticated user.
     *
     * @param User $user
     * @param UploadedFile $image
     * @return PersonalInfoResource
     * @throws Exception
     */
    public function updateProfileImage(User $user, UploadedFile $image): PersonalInfoResource
    {
        $patient = $user->patient;
        
        if (!$patient) {
            throw new Exception('Patient record not found for this user');
        }

        $personalInfo = $this->personalInfoRepository->getByPatientId($patient->id);
        
        if (!$personalInfo) {
            throw new Exception('Personal information record not found');
        }

        // Delete old image if exists
        if ($personalInfo->profile_image) {
            $oldImagePath = str_replace('/storage/', '', $personalInfo->profile_image);
            if (Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
        }

        // Store new image
        $path = $image->store('profile-images', 'public');
        $profileImageUrl = '/storage/' . $path;

        $personalInfo = $this->personalInfoRepository->update($personalInfo, [
            'profile_image' => $profileImageUrl
        ]);

        return new PersonalInfoResource($personalInfo->load(['patient.user']));
    }

    /**
     * Get personal information for a specific patient.
     *
     * @param Patient $patient
     * @return PersonalInfoResource
     * @throws Exception
     */
    public function getPatientPersonalInfo(Patient $patient): PersonalInfoResource
    {
        $personalInfo = $this->personalInfoRepository->getByPatientId($patient->id);
        
        if (!$personalInfo) {
            throw new Exception('Personal information not found for this patient');
        }

        return new PersonalInfoResource($personalInfo->load(['patient.user']));
    }

    /**
     * Update personal information for a specific patient.
     *
     * @param Patient $patient
     * @param array $data
     * @return PersonalInfoResource
     * @throws Exception
     */
    public function updatePatientPersonalInfo(Patient $patient, array $data): PersonalInfoResource
    {
        // Update user email if provided
        if (isset($data['email'])) {
            $patient->user->update(['email' => $data['email']]);
            unset($data['email']); // Remove from personal info data
        }

        $personalInfo = $this->personalInfoRepository->updateOrCreateByPatientId($patient->id, $data);

        return new PersonalInfoResource($personalInfo->load(['patient.user']));
    }
}