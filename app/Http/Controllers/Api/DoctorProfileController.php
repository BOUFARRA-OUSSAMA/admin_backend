<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\UpdateDoctorProfileRequest;
use App\Http\Requests\Doctor\UpdateProfileImageRequest;
use App\Services\DoctorProfileService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DoctorProfileController extends Controller
{
    use ApiResponseTrait;

    protected DoctorProfileService $doctorProfileService;

    public function __construct(DoctorProfileService $doctorProfileService)
    {
        $this->doctorProfileService = $doctorProfileService;
    }

    /**
     * Get authenticated doctor's profile
     */
    public function getProfile(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->isDoctor()) {
                return $this->error('Access denied. Only doctors can access this profile.', 403);
            }

            $profile = $this->doctorProfileService->getDoctorProfile($user);
            
            if (!$profile) {
                return $this->error('Doctor profile not found.', 404);
            }

            return $this->success($profile, 'Doctor profile retrieved successfully.');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve doctor profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update doctor profile
     */
    public function updateProfile(UpdateDoctorProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = $request->validated();

            $profile = $this->doctorProfileService->updateDoctorProfile($user, $data);

            return $this->success($profile, 'Doctor profile updated successfully.');

        } catch (\Exception $e) {
            return $this->error('Failed to update doctor profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update profile image
     */
    public function updateProfileImage(UpdateProfileImageRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = $request->file('image');

            $imageUrl = $this->doctorProfileService->updateProfileImage($user, $file);
            
            // Get the updated profile data
            $profile = $this->doctorProfileService->getDoctorProfile($user);

            return $this->success($profile, 'Profile image updated successfully.');

        } catch (\Exception $e) {
            return $this->error('Failed to update profile image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get available medical specialties
     */
    public function getSpecialties(): JsonResponse
    {
        try {
            $specialties = $this->doctorProfileService->getSpecialties();

            return $this->success($specialties, 'Medical specialties retrieved successfully.');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve specialties: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Test endpoint to check if controller is working
     */
    public function test(): JsonResponse
    {
        return $this->success(['message' => 'Controller is working'], 'Test successful');
    }
}
