<?php

namespace App\Services\Medical;

use App\Models\PatientFile;
use App\Models\User;
use App\Notifications\PatientFileUploadedNotification;
use Illuminate\Support\Facades\Notification;

class PatientFileNotificationService
{
    /**
     * Notify doctors when a new patient file is uploaded.
     */
    public function notifyDoctorsOfNewFile(PatientFile $file): void
    {
        try {
            // Get all doctors who should be notified
            $doctors = User::whereHas('roles', function ($query) {
                $query->where('code', 'doctor');
            })->where('status', 'active')->get();

            // Send notification to each doctor
            foreach ($doctors as $doctor) {
                $doctor->notify(new PatientFileUploadedNotification($file));
            }

            // Also create in-app notification
            $this->createInAppNotification($file, $doctors);
            
        } catch (\Exception $e) {
            // Log error but don't break the upload process
            \Log::error('Failed to send file upload notifications: ' . $e->getMessage());
        }
    }

    /**
     * Create in-app notification for file upload.
     */
    private function createInAppNotification(PatientFile $file, $doctors): void
    {
        $patient = $file->patient;
        $patientName = $patient->personalInfo->name ?? 'Unknown Patient';
        
        $data = [
            'title' => 'New Patient File Uploaded',
            'message' => "New {$file->category} file uploaded for patient {$patientName}",
            'type' => 'file_upload',
            'action_url' => "/patients/{$patient->id}/files/{$file->id}",
            'patient_id' => $patient->id,
            'file_id' => $file->id,
            'file_category' => $file->category,
            'patient_name' => $patientName
        ];

        foreach ($doctors as $doctor) {
            \App\Models\Notification::create([
                'user_id' => $doctor->id,
                'type' => 'file_upload',
                'title' => $data['title'],
                'message' => $data['message'],
                'data' => json_encode($data),
                'is_read' => false
            ]);
        }
    }

    /**
     * Notify patient when file is reviewed by doctor.
     */
    public function notifyPatientOfFileReview(PatientFile $file, User $doctor, string $review = null): void
    {
        try {
            $patient = $file->patient;
            $patientUser = $patient->user;
            
            if (!$patientUser) {
                return;
            }

            $data = [
                'title' => 'File Reviewed',
                'message' => "Your {$file->category} file has been reviewed by Dr. {$doctor->name}",
                'type' => 'file_review',
                'action_url' => "/patient/files/{$file->id}",
                'file_id' => $file->id,
                'doctor_name' => $doctor->name,
                'review' => $review
            ];

            \App\Models\Notification::create([
                'user_id' => $patientUser->id,
                'type' => 'file_review',
                'title' => $data['title'],
                'message' => $data['message'],
                'data' => json_encode($data),
                'is_read' => false
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send file review notification: ' . $e->getMessage());
        }
    }
}
