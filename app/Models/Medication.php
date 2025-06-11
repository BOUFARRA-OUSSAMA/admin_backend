<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medication extends Model
{
    protected $table = 'prescriptions'; // Use existing prescriptions table

    protected $fillable = [
        'patient_id',           // NEW: Direct patient relationship
        'chart_patient_id',     // Keep for backward compatibility
        'doctor_user_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration',
        'start_date',
        'end_date',
        'instructions',
        'refills_allowed',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the patient who owns the medication (DIRECT RELATIONSHIP).
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor who prescribed the medication.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }

    /**
     * Transform medication data for frontend display.
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->medication_name,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'status' => $this->status,
            'startDate' => $this->start_date?->toDateString(),
            'endDate' => $this->end_date?->toDateString(),
            'instructions' => $this->instructions,
            'refillsAllowed' => $this->refills_allowed,
            'prescribedBy' => $this->doctor?->name,
            'isActive' => $this->status === 'active',
            'isExpired' => $this->end_date && $this->end_date->isPast(),
        ];
    }

    /**
     * Scope for active medications.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for current medications (not expired).
     */
    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }
}
