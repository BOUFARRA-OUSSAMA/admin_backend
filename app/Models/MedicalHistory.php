<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    protected $table = 'medical_histories';

    protected $fillable = [
        'patient_id',
        'currentMedicalConditions',
        'pastSurgeries',
        'chronicDiseases',
        'currentMedications',
        'allergies',
        'lastUpdated',
    ];

    protected $casts = [
        'currentMedicalConditions' => 'array',
        'pastSurgeries' => 'array',
        'chronicDiseases' => 'array',
        'currentMedications' => 'array',
        'allergies' => 'array',
        'lastUpdated' => 'datetime',
    ];
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
  
}