<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTest extends Model
{
    protected $table = 'lab_tests';

    protected $fillable = [
        'patient_id',           // NEW: Direct patient relationship
        'chart_patient_id',     // Keep for backward compatibility
        'requested_by_user_id',
        'test_name',
        'test_code',
        'urgency',
        'requested_date',
        'scheduled_date',
        'lab_name',
        'status',
    ];

    protected $casts = [
        'requested_date' => 'datetime',
        'scheduled_date' => 'datetime',
    ];

    /**
     * Get the patient who owns the lab test (DIRECT RELATIONSHIP).
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who requested the test.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Get the lab results for this test.
     */
    public function labResults(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    /**
     * Transform lab test for frontend display.
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'testName' => $this->test_name,
            'testCode' => $this->test_code,
            'urgency' => $this->urgency,
            'status' => $this->status,
            'requestedDate' => $this->requested_date->toISOString(),
            'scheduledDate' => $this->scheduled_date?->toISOString(),
            'labName' => $this->lab_name,
            'requestedBy' => $this->requestedBy?->name,
            'hasResults' => $this->labResults()->exists(),
            'resultsCount' => $this->labResults()->count(),
        ];
    }

    /**
     * Scope for pending tests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed tests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
