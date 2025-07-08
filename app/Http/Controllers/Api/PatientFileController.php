<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientFile;
use App\Models\Patient;
use App\Services\Medical\FileUploadService;
use App\Services\Medical\TimelineEventService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PatientFileController extends Controller
{
    use ApiResponseTrait;

    protected FileUploadService $fileUploadService;
    protected TimelineEventService $timelineEventService;

    public function __construct(
        FileUploadService $fileUploadService,
        TimelineEventService $timelineEventService
    ) {
        $this->fileUploadService = $fileUploadService;
        $this->timelineEventService = $timelineEventService;
    }

    /**
     * Display a listing of files for a patient.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get patient ID from request or current user
            $patientId = $request->query('patient_id');
            if (!$patientId && $user->isPatient()) {
                $patient = $user->patient;
                $patientId = $patient ? $patient->id : null;
            }
            
            if (!$patientId) {
                return $this->error('Patient ID is required', 400);
            }

            $patient = Patient::findOrFail($patientId);
            
            // Check permissions for viewing files
            $canViewFiles = $user->hasPermission('patients:view-files') || $user->isDoctor();
            
            if (!$canViewFiles && !$user->isPatient()) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $patient->id) {
                return $this->error('Access denied', 403);
            }

            $query = PatientFile::where('patient_id', $patient->id)
                ->with(['uploadedBy', 'patient.personalInfo']);

            // Apply filters
            if ($request->query('category')) {
                $query->where('category', $request->query('category'));
            }
            
            if ($request->query('date_from')) {
                $query->whereDate('created_at', '>=', $request->query('date_from'));
            }
            
            if ($request->query('date_to')) {
                $query->whereDate('created_at', '<=', $request->query('date_to'));
            }

            $files = $query->orderBy('created_at', 'desc')
                ->limit($request->query('limit', 50))
                ->get()
                ->map(function ($file) {
                    return $file->toFrontendFormat();
                });
            
            return $this->success($files, 'Patient files retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve patient files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly uploaded file.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'file' => 'required|file|max:25600', // 25MB max
                'category' => 'required|in:xray,scan,lab_report,insurance,other,prescription,document',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            
            // Check permissions for file upload
            $canManageFiles = $user->hasPermission('patients:manage-files') || $user->isDoctor();
            
            if (!$canManageFiles) {
                // Only patients can upload to their own records if they don't have manage-files permission
                if (!$user->isPatient()) {
                    return $this->error('Insufficient permissions', 403);
                }
                
                // Patients can only upload to their own records
                if ($user->patient->id != $request->patient_id) {
                    return $this->error('Access denied', 403);
                }
            }

            $patient = Patient::findOrFail($request->patient_id);
            
            // Upload file using service
            $uploadResult = $this->fileUploadService->uploadPatientFile(
                $request->file('file'),
                $patient->id,
                $request->category,
                $user->id
            );

            // Create file record
            $patientFile = PatientFile::create([
                'patient_id' => $patient->id,
                'original_filename' => $uploadResult['original_name'],
                'stored_filename' => basename($uploadResult['file_path']),
                'file_path' => $uploadResult['file_path'],
                'file_size' => $uploadResult['file_size'],
                'mime_type' => $uploadResult['mime_type'],
                'file_type' => $uploadResult['file_type'],
                'category' => $request->category,
                'description' => $request->description,
                'is_visible_to_patient' => !$request->boolean('is_private', false),
                'uploaded_by_user_id' => $user->id,
                'uploaded_at' => now()
            ]);

            // Create timeline event
            $this->timelineEventService->createFileUploadEvent($patientFile, $user);

            // Send notification to doctors if not private
            if (!$patientFile->is_private) {
                $this->fileUploadService->notifyDoctorsOfNewFile($patientFile);
            }

            return $this->success(
                $patientFile->toFrontendFormat(),
                'File uploaded successfully',
                201
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to upload file: ' . $e->getMessage(), 500);
        }
    }


        /**
     * Store a new file for the currently authenticated patient.
     * ✅ NOUVELLE MÉTHODE
     */
    public function uploadForPatient(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:25600', // 25MB max
                'category' => 'required|in:xray,scan,lab_report,insurance,other,prescription,document',
                'description' => 'nullable|string|max:1000'
            ]);
             if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();

            // Ensure the user is a patient and has a patient record
            if (!$user->isPatient() || !$user->patient) {
                return $this->error('You must be a patient to upload files to your record.', 403);
            }
            
            $patient = $user->patient;
             // Upload file using service
            $uploadResult = $this->fileUploadService->uploadPatientFile(
                $request->file('file'),
                $patient->id,
                $request->category,
                $user->id
            );
             // Create file record
            $patientFile = PatientFile::create([
                'patient_id' => $patient->id,
                'original_filename' => $uploadResult['original_name'],
                'stored_filename' => basename($uploadResult['file_path']),
                'file_path' => $uploadResult['file_path'],
                'file_size' => $uploadResult['file_size'],
                'mime_type' => $uploadResult['mime_type'],
                'file_type' => $uploadResult['file_type'],
                'category' => $request->category,
                'description' => $request->description,
                'is_visible_to_patient' => true, // Files uploaded by patient are always visible to them
                'uploaded_by_user_id' => $user->id,
                'uploaded_at' => now()
            ]);
             // Create timeline event
            $this->timelineEventService->createFileUploadEvent($patientFile, $user);

            // Send notification to doctors
            $this->fileUploadService->notifyDoctorsOfNewFile($patientFile);

            return $this->success(
                $patientFile->toFrontendFormat(),
                'File uploaded successfully',
                201
            );
             } catch (\Exception $e) {
            return $this->error('Failed to upload file: ' . $e->getMessage(), 500);
        }
    }

    

    /**
     * Display the specified file metadata.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = PatientFile::with(['patient.personalInfo', 'uploadedBy'])->findOrFail($id);
            
            // Check permissions for viewing file details
            $canViewFiles = $user->hasPermission('patients:view-files') || $user->isDoctor();
            
            if (!$canViewFiles && !$user->isPatient()) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $file->patient_id) {
                return $this->error('Access denied', 403);
            }
            
            // Check if file is private and user has access
            if ($file->is_private && !$user->isDoctor() && 
                ($user->isPatient() && $user->patient->id !== $file->patient_id)) {
                return $this->error('Access denied to private file', 403);
            }

            return $this->success(
                $file->toFrontendFormat(),
                'File details retrieved successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve file details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Download the specified file.
     */
    public function download(string $id): mixed
    {
        try {
            $user = Auth::user();
            $file = PatientFile::findOrFail($id);
            
            // Check permissions for downloading files
            $canViewFiles = $user->hasPermission('patients:view-files') || $user->isDoctor();
            
            if (!$canViewFiles && !$user->isPatient()) {
                return $this->error('Insufficient permissions', 403);
            }
            
            if ($user->isPatient() && $user->patient->id !== $file->patient_id) {
                return $this->error('Access denied', 403);
            }
            
            // Check if file is private and user has access
            if ($file->is_private && !$user->isDoctor() && 
                ($user->isPatient() && $user->patient->id !== $file->patient_id)) {
                return $this->error('Access denied to private file', 403);
            }

            // Check if file exists
            if (!Storage::disk('local')->exists($file->file_path)) {
                return $this->error('File not found on disk', 404);
            }

            // Note: Download count tracking removed due to missing column

            return Storage::disk('local')->download($file->file_path, $file->original_filename);
            
        } catch (\Exception $e) {
            return $this->error('Failed to download file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified file metadata.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'description' => 'nullable|string|max:1000',
                'category' => 'sometimes|in:xray,scan,lab_report,insurance,other',
                'is_visible_to_patient' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $file = PatientFile::findOrFail($id);
            
            // Check permissions for updating files
            $canManageFiles = $user->hasPermission('patients:manage-files') || $user->isDoctor();
            
            if (!$canManageFiles) {
                // Patients can only edit their own files
                if (!$user->isPatient()) {
                    return $this->error('Insufficient permissions', 403);
                }
                
                if ($user->patient->id !== $file->patient_id) {
                    return $this->error('Access denied', 403);
                }
            }

            $file->update($request->only(['description', 'category', 'is_visible_to_patient']));

            return $this->success(
                $file->fresh()->toFrontendFormat(),
                'File updated successfully'
            );
            
        } catch (\Exception $e) {
            return $this->error('Failed to update file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified file.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check permissions for deleting files
            $canManageFiles = $user->hasPermission('patients:manage-files') || $user->isDoctor();
            
            if (!$canManageFiles) {
                return $this->error('Insufficient permissions', 403);
            }

            $file = PatientFile::findOrFail($id);
            
            // Delete physical file
            if (Storage::disk('local')->exists($file->file_path)) {
                Storage::disk('local')->delete($file->file_path);
            }

            $file->delete();

            return $this->success(null, 'File deleted successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to delete file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get file categories.
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = [
                'xray' => 'X-Ray',
                'scan' => 'CT/MRI Scan',
                'lab_report' => 'Lab Report',
                'insurance' => 'Insurance Document',
                'prescription' => 'Prescription',
                'document' => 'General Document',
                'other' => 'Other'
            ];

            return $this->success($categories, 'File categories retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve file categories: ' . $e->getMessage(), 500);
        }
    }
}
