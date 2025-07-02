<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DoctorProfileService
{
    /**
     * Get doctor profile with user data
     */
    public function getDoctorProfile(User $user): ?array
    {
        
        
        try {
            $doctor = $user->doctor()->first();
           
            
            if (!$doctor) {
               
                return null;
            }
            
            
            
            return [
                // User data
                'id' => $doctor->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_image' => $user->profile_image ? Storage::url($user->profile_image) : null,
                
                // Doctor data
                'license_number' => $doctor->license_number,
                'specialty' => $doctor->specialty,
                'experience_years' => $doctor->experience_years,
                'consultation_fee' => $doctor->consultation_fee,
                'is_available' => $doctor->is_available,
                'working_hours' => $doctor->working_hours,
                'max_patient_appointments' => $doctor->max_patient_appointments,
                
                // Timestamps
                'created_at' => $doctor->created_at,
                'updated_at' => $doctor->updated_at,
            ];
        } catch (\Exception $e) {
            
            throw $e;
        }
    }
    
    /**
     * Update doctor profile
     */
    public function updateDoctorProfile(User $user, array $data): array
    {
        DB::beginTransaction();
        
        try {
            // Update user data
            $userFields = ['name', 'email', 'phone'];
            $userData = array_intersect_key($data, array_flip($userFields));
            if (!empty($userData)) {
                $user->update($userData);
            }
            
            // Update doctor data
            $doctorFields = [
                'license_number', 'specialty', 'experience_years', 
                'consultation_fee', 'is_available', 'working_hours',
                'max_patient_appointments'
            ];
            $doctorData = array_intersect_key($data, array_flip($doctorFields));
            
            if (!empty($doctorData)) {
                
                $user->doctor()->update($doctorData);
            }
            
            DB::commit();
            
            return $this->getDoctorProfile($user);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Update profile image
     */
    public function updateProfileImage(User $user, UploadedFile $file): string
    {
        // Delete old image if exists
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }
        
        // Store new image
        $path = $file->store('profile_images', 'public');
        
        // Update user record
        $user->update(['profile_image' => $path]);
        
        // Refresh the user model to ensure we have the latest data
        $user->refresh();
        
        return Storage::url($path);
    }
    
    /**
     * Get available specialties
     */
    public function getSpecialties(): array
    {
        return [
            'Cardiology', 'Dermatology', 'Endocrinology', 'Gastroenterology',
            'Hematology', 'Infectious Disease', 'Nephrology', 'Neurology',
            'Obstetrics/Gynecology', 'Oncology', 'Ophthalmology', 'Orthopedics',
            'Otolaryngology', 'Pediatrics', 'Psychiatry', 'Pulmonology',
            'Radiology', 'Rheumatology', 'Urology', 'Family Medicine',
            'Internal Medicine', 'Emergency Medicine', 'General Surgery',
            'Plastic Surgery', 'Anesthesiology', 'Physical Medicine',
            'Allergy/Immunology', 'Dental Medicine'
        ];
    }
}
