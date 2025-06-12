<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientNote extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'note_type',
        'title',
        'content',
        'is_private',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    /**
     * Get the patient that owns the note.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor who wrote the note.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the user who created the note (alias for doctor relationship).
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Transform note for frontend display.
     */
    public function toFrontendFormat(bool $isPatientView = false): array
    {
        // Hide private notes from patient view
        if ($isPatientView && $this->is_private) {
            return null;
        }

        return [
            'id' => $this->id,
            'type' => $this->note_type,
            'title' => $this->title,
            'content' => $this->content,
            'isPrivate' => $this->is_private,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
            'doctorName' => $this->createdBy?->name ?? $this->doctor?->name,
            'createdBy' => $this->createdBy?->name ?? $this->doctor?->name,
            'canEdit' => !$isPatientView, // Only doctors can edit
        ];
    }

    /**
     * Scope for public notes (visible to patients).
     */
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    /**
     * Scope for private notes (doctor only).
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * Scope by note type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('note_type', $type);
    }

    /**
     * Scope by doctor.
     */
    public function scopeByDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }
}
