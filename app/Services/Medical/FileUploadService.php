<?php

namespace App\Services\Medical;

use App\Models\Patient;
use App\Models\PatientFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    protected PatientFileNotificationService $notificationService;

    public function __construct(PatientFileNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Allowed file types and their configurations.
     */
    private const FILE_TYPES = [
        'image' => [
            'extensions' => ['jpeg', 'jpg', 'png'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'mime_types' => ['image/jpeg', 'image/png']
        ],
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx'],
            'max_size' => 25 * 1024 * 1024, // 25MB
            'mime_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        ]
    ];

    private const CATEGORIES = ['xray', 'scan', 'lab_report', 'insurance', 'other'];

    private const STORAGE_PATH = 'patient-files';    /**
     * Upload a patient file.
     */
    public function uploadPatientFile(
        UploadedFile $file,
        int $patientId,
        string $category,
        int $uploadedByUserId
    ): array {
        $patient = Patient::findOrFail($patientId);
        $uploadedBy = User::findOrFail($uploadedByUserId);
        
        // Validate file
        $this->validateFile($file);
        $this->validateCategory($category);

        // Determine file type
        $fileType = $this->determineFileType($file);

        // Generate unique filename
        $storedFilename = $this->generateUniqueFilename($file);

        // Create directory path
        $directoryPath = $this->createDirectoryPath($patient->id);

        // Store file
        $filePath = $file->storeAs($directoryPath, $storedFilename, 'local');

        return [
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_type' => $fileType
        ];
    }    /**
     * Notify doctors when a new patient file is uploaded.
     */
    public function notifyDoctorsOfNewFile(PatientFile $file): void
    {
        $this->notificationService->notifyDoctorsOfNewFile($file);
    }

    /**
     * Get patient files with optional filtering.
     */
    public function getPatientFiles(
        Patient $patient,
        ?string $category = null,
        ?string $fileType = null,
        bool $visibleToPatientOnly = false
    ): array {
        $query = $patient->patientFiles()->latest('uploaded_at');

        if ($category) {
            $query->where('category', $category);
        }

        if ($fileType) {
            $query->where('file_type', $fileType);
        }

        if ($visibleToPatientOnly) {
            $query->where('is_visible_to_patient', true);
        }

        return $query->get()->map(function ($file) {
            return $file->toFrontendFormat();
        })->toArray();
    }

    /**
     * Search patient files.
     */
    public function searchPatientFiles(Patient $patient, string $searchTerm): array
    {
        $files = $patient->patientFiles()
            ->where(function ($query) use ($searchTerm) {
                $query->where('original_filename', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'ILIKE', "%{$searchTerm}%")
                      ->orWhere('category', 'ILIKE', "%{$searchTerm}%");
            })
            ->latest('uploaded_at')
            ->get();

        return $files->map(function ($file) {
            return $file->toFrontendFormat();
        })->toArray();
    }

    /**
     * Delete a patient file.
     */
    public function deletePatientFile(PatientFile $file): bool
    {
        // Delete from storage
        if (Storage::disk('local')->exists($file->file_path)) {
            Storage::disk('local')->delete($file->file_path);
        }

        // Delete database record
        return $file->delete();
    }

    /**
     * Get file download path.
     */
    public function getFileDownloadPath(PatientFile $file): string
    {
        return Storage::disk('local')->path($file->file_path);
    }

    /**
     * Validate uploaded file.
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        $fileType = $this->determineFileType($file);
        $config = self::FILE_TYPES[$fileType];

        // Check file size
        if ($file->getSize() > $config['max_size']) {
            throw new \InvalidArgumentException(
                "File size exceeds maximum allowed size of " . ($config['max_size'] / 1024 / 1024) . "MB"
            );
        }

        // Check mime type
        if (!in_array($file->getMimeType(), $config['mime_types'])) {
            throw new \InvalidArgumentException('File type not allowed');
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $config['extensions'])) {
            throw new \InvalidArgumentException('File extension not allowed');
        }
    }

    /**
     * Validate category.
     */
    private function validateCategory(string $category): void
    {
        if (!in_array($category, self::CATEGORIES)) {
            throw new \InvalidArgumentException('Invalid file category');
        }
    }

    /**
     * Determine file type from uploaded file.
     */
    private function determineFileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        return 'document';
    }

    /**
     * Generate unique filename.
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $uuid = Str::uuid();
        
        return "{$uuid}.{$extension}";
    }

    /**
     * Create directory path for patient files.
     */
    private function createDirectoryPath(int $patientId): string
    {
        return self::STORAGE_PATH . '/' . $patientId;
    }

    /**
     * Get allowed file types for frontend.
     */
    public function getAllowedFileTypes(): array
    {
        return [
            'categories' => self::CATEGORIES,
            'types' => [
                'image' => [
                    'extensions' => self::FILE_TYPES['image']['extensions'],
                    'max_size_mb' => self::FILE_TYPES['image']['max_size'] / 1024 / 1024
                ],
                'document' => [
                    'extensions' => self::FILE_TYPES['document']['extensions'],
                    'max_size_mb' => self::FILE_TYPES['document']['max_size'] / 1024 / 1024
                ]
            ]
        ];
    }
}
