<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartPatient extends Model
{
    use HasFactory;

    protected $table = 'chart_patients'; // Confirmez le nom de la table

    protected $fillable = [
        'patient_id', // Référence à users.id
        'diagnosis',
        'chief_complaint',
        'status',
        'followup_date',
    ];

    protected $casts = [
        'followup_date' => 'datetime',
    ];

 
        /**
     * Get the patient that owns this chart.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the treatments for this patient chart.
     */
    // public function treatments(): HasMany
    // {
    //     return $this->hasMany(Treatment::class);
    // }

        /**
     * Get the lab tests for this patient chart.
     */
    // public function labTests(): HasMany
    // {
    //     return $this->hasMany(LabTest::class);
    // }

    // public function doctor(): BelongsTo // Médecin principal associé à ce dossier patient
    // {
    //     return $this->belongsTo(Doctor::class, 'doctor_id');
    // }

     /**
     * Get the medical records associated with this patient chart.
     */
    // public function medicalRecords(): HasMany
    // {
    //     return $this->hasMany(MedicalRecord::class, 'chart_patient_id');
    // }

    /**
     * Get the prescriptions for this patient chart.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'chart_patient_id');
    }




 








}