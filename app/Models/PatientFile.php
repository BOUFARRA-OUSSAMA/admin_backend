<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PatientFile extends Model
{
    protected $fillable = [
        'patient_id',
        'uploaded_by_user_id',
        'file_type',
        'category',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'mime_type',
        'description',
        'is_visible_to_patient',
        'uploaded_at',
    ];

    protected $casts = [
        'is_visible_to_patient' => 'boolean',
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the patient that owns the file.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Transform file for frontend display.
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->file_type,
            'category' => $this->category,
            'originalFilename' => $this->original_filename,
            'description' => $this->description,
            'fileSize' => $this->file_size,
            'fileSizeFormatted' => $this->getFormattedFileSize(),
            'mimeType' => $this->mime_type,
            'uploadedAt' => $this->uploaded_at->toISOString(),
            'uploadedBy' => $this->uploadedBy?->name,
            'isVisibleToPatient' => $this->is_visible_to_patient,
            'downloadUrl' => route('patient-files.download', [
                'file' => $this->id
            ]),
            'categoryLabel' => $this->getCategoryLabel(),
            'typeIcon' => $this->getTypeIcon(),
        ];
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get category label.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            'xray' => 'X-Ray',
            'scan' => 'Medical Scan',
            'lab_report' => 'Lab Report',
            'insurance' => 'Insurance Document',
            'other' => 'Other',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get type icon.
     */
    public function getTypeIcon(): string
    {
        if ($this->file_type === 'image') {
            return 'image';
        }
        
        return match ($this->category) {
            'xray', 'scan' => 'x-ray',
            'lab_report' => 'file-medical',
            'insurance' => 'file-contract',
            default => 'file',
        };
    }

    /**
     * Get full file path for storage.
     */
    public function getFullPath(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Check if file exists on disk.
     */
    public function exists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Delete file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::delete($this->file_path);
        }
        return true;
    }

    /**
     * Scope for images.
     */
    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }

    /**
     * Scope for documents.
     */
    public function scopeDocuments($query)
    {
        return $query->where('file_type', 'document');
    }

    /**
     * Scope by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for patient-visible files.
     */
    public function scopeVisibleToPatient($query)
    {
        return $query->where('is_visible_to_patient', true);
    }

    /**
     * Search files by filename or description.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('original_filename', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
