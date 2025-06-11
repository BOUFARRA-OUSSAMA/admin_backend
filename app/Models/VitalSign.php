<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    protected $table = 'vital_signs';

    protected $fillable = [
        'patient_id',
        'recorded_by_user_id',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse_rate',
        'temperature',
        'temperature_unit',
        'respiratory_rate',
        'oxygen_saturation',
        'weight',
        'weight_unit',
        'height',
        'height_unit',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature' => 'decimal:1',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    /**
     * Get the patient that owns the vital sign.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who recorded the vital sign.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * Transform vital signs for frontend display.
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'bloodPressure' => [
                'systolic' => $this->blood_pressure_systolic,
                'diastolic' => $this->blood_pressure_diastolic,
                'reading' => $this->blood_pressure_systolic . '/' . $this->blood_pressure_diastolic,
            ],
            'pulseRate' => $this->pulse_rate,
            'temperature' => [
                'value' => $this->temperature,
                'unit' => $this->temperature_unit,
                'display' => $this->temperature . $this->temperature_unit,
            ],
            'respiratoryRate' => $this->respiratory_rate,
            'oxygenSaturation' => $this->oxygen_saturation,
            'weight' => [
                'value' => $this->weight,
                'unit' => $this->weight_unit,
                'display' => $this->weight . ' ' . $this->weight_unit,
            ],
            'height' => [
                'value' => $this->height,
                'unit' => $this->height_unit,
                'display' => $this->height . ' ' . $this->height_unit,
            ],
            'notes' => $this->notes,
            'recordedAt' => $this->recorded_at->toISOString(),
            'recordedBy' => $this->recordedBy?->name,
        ];
    }
}
