<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAlert extends Model
{
    protected $fillable = [
        'patient_id',
        'alert_type',
        'severity',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the patient that owns the alert.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Transform alert for frontend display.
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->alert_type,
            'severity' => $this->severity,
            'title' => $this->title,
            'description' => $this->description,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
            'severityColor' => $this->getSeverityColor(),
            'severityIcon' => $this->getSeverityIcon(),
        ];
    }

    /**
     * Get color based on severity.
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get icon based on severity.
     */
    public function getSeverityIcon(): string
    {
        return match ($this->severity) {
            'critical' => 'exclamation-triangle',
            'high' => 'exclamation-circle',
            'medium' => 'info-circle',
            'low' => 'info',
            default => 'bell',
        };
    }

    /**
     * Scope for active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope by alert type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope for critical alerts.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}
