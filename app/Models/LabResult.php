<?php
 
 namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    use HasFactory;

    protected $table = 'lab_results';

    protected $fillable = [
        'medical_record_id',
        'result_date',
        'performed_by',
        'test_path',
        'status', // 'pending', 'completed', 'reviewed', 'cancelled'
        'interpretation',
    ];

    protected $casts = [
        'result_date' => 'datetime',
    ];

     /**
     * Get the medical record that owns this lab result.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }



 
}