<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalHistory extends Model
{
    protected $table = 'medical_histories';

    protected $fillable = [
        'patient_id',
        'current_medical_conditions',
        'past_surgeries',
        'chronic_diseases',
        'current_medications',
        'allergies',
        'last_updated',
        'updated_by_user_id',
    ];

    protected $casts = [
        'current_medical_conditions' => 'array',
        'past_surgeries' => 'array',
        'chronic_diseases' => 'array',
        'current_medications' => 'array',
        'allergies' => 'array',
        'last_updated' => 'datetime',
    ];

    /**
     * Get the patient that owns the medical history.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who last updated the medical history.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * Transform medical history for frontend display.
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'conditions' => $this->current_medical_conditions ?? [],
            'surgeries' => $this->past_surgeries ?? [],
            'chronicDiseases' => $this->chronic_diseases ?? [],
            'medications' => $this->current_medications ?? [],
            'allergies' => $this->allergies ?? [],
            'lastUpdated' => $this->last_updated?->toISOString(),
            'updatedBy' => $this->updatedBy?->name,
        ];
    }

    /**
     * Check if patient has specific allergy.
     */
    public function hasAllergy(string $allergen): bool
    {
        $allergies = $this->allergies ?? [];
        return in_array(strtolower($allergen), array_map('strtolower', $allergies));
    }

    /**
     * Check if patient has specific condition.
     */
    public function hasCondition(string $condition): bool
    {
        $conditions = $this->current_medical_conditions ?? [];
        return in_array(strtolower($condition), array_map('strtolower', $conditions));
    }

    /**
     * Get all critical information for alerts.
     */
    public function getCriticalInfo(): array
    {
        return [
            'allergies' => $this->allergies ?? [],
            'chronic_diseases' => $this->chronic_diseases ?? [],
            'current_medications' => $this->current_medications ?? [],
        ];
    }
}
