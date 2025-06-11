<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvent extends Model
{
    protected $fillable = [
        'patient_id',
        'event_type',
        'title',
        'description',
        'event_date',
        'related_id',
        'related_type',
        'importance',
        'is_visible_to_patient',
        'created_by_user_id',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'is_visible_to_patient' => 'boolean',
    ];

    /**
     * Get the patient that owns the timeline event.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who created the event.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the related model (polymorphic relationship).
     */
    public function related()
    {
        if ($this->related_type && $this->related_id) {
            return $this->morphTo('related', 'related_type', 'related_id');
        }
        return null;
    }

    /**
     * Transform timeline event for frontend display.
     */
    public function toFrontendFormat(bool $isPatientView = false): array
    {
        // Hide private events from patient view
        if ($isPatientView && !$this->is_visible_to_patient) {
            return null;
        }

        return [
            'id' => $this->id,
            'type' => $this->event_type,
            'title' => $this->title,
            'description' => $this->description,
            'eventDate' => $this->event_date->toISOString(),
            'importance' => $this->importance,
            'isVisibleToPatient' => $this->is_visible_to_patient,
            'createdBy' => $this->createdBy?->name,
            'createdAt' => $this->created_at->toISOString(),
            'relatedType' => $this->related_type,
            'relatedId' => $this->related_id,
            'importanceColor' => $this->getImportanceColor(),
            'typeIcon' => $this->getTypeIcon(),
        ];
    }

    /**
     * Get color based on importance.
     */
    public function getImportanceColor(): string
    {
        return match ($this->importance) {
            'high' => 'red',
            'medium' => 'orange',
            'low' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get icon based on event type.
     */
    public function getTypeIcon(): string
    {
        return match ($this->event_type) {
            'appointment' => 'calendar',
            'prescription' => 'pills',
            'vital_signs' => 'heartbeat',
            'note' => 'file-text',
            'file_upload' => 'upload',
            'manual' => 'edit',
            default => 'clock',
        };
    }

    /**
     * Scope for patient-visible events.
     */
    public function scopeVisibleToPatient($query)
    {
        return $query->where('is_visible_to_patient', true);
    }

    /**
     * Scope by event type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope by importance.
     */
    public function scopeByImportance($query, string $importance)
    {
        return $query->where('importance', $importance);
    }

    /**
     * Scope for recent events.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('event_date', '>=', now()->subDays($days));
    }
}
