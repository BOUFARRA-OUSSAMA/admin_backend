<?php
 
 namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class LabTest extends Model
{
    use HasFactory;

    protected $table = 'lab_tests';

    protected $fillable = [
        'chart_patient_id',
        'test_name',
        'test_code',
        'urgency',
        'requested_date',
        'scheduled_date',
        'lab_name',
    ];

    protected $casts = [
        'requested_date' => 'datetime',
        'scheduled_date' => 'datetime',
    ];


    /**
     * Get the chart patient that owns this lab test.
     */
    public function chartPatient(): BelongsTo
    {
        return $this->belongsTo(ChartPatient::class, 'chart_patient_id');
    }


   
}