<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $table = 'prescriptions';
    protected $fillable = [
        'chart_patient_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration',
        'start_date',
        'end_date',
        'instructions',
        'refills',
        'status', // Ajouté
        'doctor_name', // Ajouté
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'quantity' => 'integer',
    ];
    public function chartPatient()
    {
        return $this->belongsTo(ChartPatient::class, 'chart_patient_id');
    }

    // public function doctor()
    // {
    //     return $this->belongsTo(EloquentDoctor::class);
    // }
}
