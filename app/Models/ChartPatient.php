<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartPatient extends Model
{
    protected $fillable = [
        'patient_user_id',
        'chief_complaint',
        'diagnosis',
        'status',
        'followup_date',
        'created_by_user_id',
    ];

    protected $casts = [
        'followup_date' => 'date',
    ];

    /**
     * Get the patient user that owns this chart.
     */
    public function patientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_user_id');
    }

    /**
     * Get the patient record through user relationship.
     */
    public function patient()
    {
        return $this->patientUser->patient ?? null;
    }

    /**
     * Get the user who created this chart.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get prescriptions for this chart patient.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Medication::class, 'chart_patient_id');
    }

    /**
     * Scope for active charts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
