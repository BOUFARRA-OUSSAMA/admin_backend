<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalInfo\UpdatePersonalInfoRequest;
use App\Http\Requests\PersonalInfo\UpdateProfileImageRequest;
use App\Models\Patient;
use App\Services\PersonalInfoService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PersonalInfoController extends Controller
{
    use ApiResponseTrait;

    protected PersonalInfoService $personalInfoService;

    public function __construct(PersonalInfoService $personalInfoService)
    {
        $this->personalInfoService = $personalInfoService;
    }

    /**
     * Get authenticated user's personal information.
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isPatient()) {
                return $this->error('Access denied. Only patients can access personal info.', 403);
            }

            $personalInfo = $this->personalInfoService->getUserPersonalInfo($user);
            
            return $this->success($personalInfo, 'Personal information retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve personal information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update authenticated user's personal information.
     *
     * @param UpdatePersonalInfoRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdatePersonalInfoRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isPatient()) {
                return $this->error('Access denied. Only patients can update personal info.', 403);
            }

            $personalInfo = $this->personalInfoService->updateUserPersonalInfo($user, $request->validated());
            
            return $this->success($personalInfo, 'Personal information updated successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to update personal information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update authenticated user's profile image.
     *
     * @param UpdateProfileImageRequest $request
     * @return JsonResponse
     */
    public function updateProfileImage(UpdateProfileImageRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isPatient()) {
                return $this->error('Access denied. Only patients can update profile image.', 403);
            }

            $personalInfo = $this->personalInfoService->updateProfileImage($user, $request->file('profile_image'));
            
            return $this->success($personalInfo, 'Profile image updated successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to update profile image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get personal information for a specific patient (Admin/Staff access).
     *
     * @param Patient $patient
     * @return JsonResponse
     */
    public function getPatientPersonalInfo(Patient $patient): JsonResponse
    {
        try {
            $personalInfo = $this->personalInfoService->getPatientPersonalInfo($patient);
            
            return $this->success($personalInfo, 'Patient personal information retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient personal information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update personal information for a specific patient (Admin/Staff access).
     *
     * @param UpdatePersonalInfoRequest $request
     * @param Patient $patient
     * @return JsonResponse
     */
    public function updatePatientPersonalInfo(UpdatePersonalInfoRequest $request, Patient $patient): JsonResponse
    {
        try {
            $personalInfo = $this->personalInfoService->updatePatientPersonalInfo($patient, $request->validated());
            
            return $this->success($personalInfo, 'Patient personal information updated successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to update patient personal information: ' . $e->getMessage(), 500);
        }
    }
}