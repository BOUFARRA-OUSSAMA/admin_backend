<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
 
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
class MedicalRecord extends Model
{
    use HasFactory;

    protected $table = 'medical_records';

    protected $fillable = [
        'user_id',
        'patient_id', // Assurez-vous que cela pointe vers la table 'patients'
        'doctor_id',
        'record_type_id',
        'title',
        'description',
        'metadata',
        'record_date',
        'is_confidential',
        'status',
        'version',
        'record_class_type',
        'record_class_id',
        'chart_patient_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'record_date' => 'datetime',
        'is_confidential' => 'boolean',
    ];
 

 

  
        /**
     * Get the doctor associated with this medical record.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Get the record type for this record.
     */
    public function recordType(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * Get the chart patient associated with this medical record.
     */
    public function chartPatient(): BelongsTo
    {
        return $this->belongsTo(ChartPatient::class);
    }


//    /**
//      * Get the attachments for this medical record.
//      */
//     public function attachments(): HasMany
//     {
//         return $this->hasMany(RecordAttachment::class);
//     }

    // /**
    //  * Get the versions for this medical record.
    //  */
    // public function versions(): HasMany
    // {
    //     return $this->hasMany(RecordVersion::class);
    // }
    // Relation pour les détails spécifiques du résultat de laboratoire
   
     /**
     * Get the lab result record if this is a lab result type.
     */
    public function labResult(): HasOne
    {
        return $this->hasOne(LabResult::class);
    }


    //     /**
    //  * Get the scanned script record if this is a scanned script type.
    //  */
    // public function scannedScript(): HasOne
    // {
    //     return $this->hasOne(ScannedScript::class);
    // }

    //  /**
    //  * Get the medical image record if this is a medical image type.
    //  */
    // public function medicalImage(): HasOne
    // {
    //     return $this->hasOne(MedicalImage::class);
    // }

    //     /**
    //  * Scope a query to only include records for a specific patient.
    //  */
    // public function scopeForPatient($query, $patientId)
    // {
    //     return $query->where(function ($query) use ($patientId) {
    //         $query->where('user_id', $patientId)
    //               ->orWhere('patient_id', $patientId);
    //     });
    // }

    //     /**
    //  * Scope a query to only include active records.
    //  */
    // public function scopeActive($query)
    // {
    //     return $query->where('status', 'active');
    // }
}