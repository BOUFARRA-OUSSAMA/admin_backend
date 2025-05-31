<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalInfo extends Model
{
    protected $fillable = [
        'patient_id',
        'name',
        'surname',
        'birthdate',
        'gender',
        'address',
        'emergency_contact',
        'marital_status',
        'blood_type',
        'nationality',
        'profile_image'
    ];

    /**
     * Get the patient that owns this personal info.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
