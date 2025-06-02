<?php
 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Patient; // Assurez-vous que le chemin vers votre modèle Patient Eloquent est correct

class VitalSign extends Model
{
    protected $table = 'vital_signs'; // Nom de la table de migration

    protected $fillable = [
        'patient_id',
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
    ];

    // created_at et updated_at sont gérés automatiquement par Eloquent

    protected $casts = [
        'pulse_rate' => 'integer',
        'temperature' => 'float',
        'respiratory_rate' => 'integer',
        'oxygen_saturation' => 'integer',
        'weight' => 'float',
        'height' => 'float',
    ];

    /**
     * Relation avec le patient.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}