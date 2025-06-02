<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_user_id',
        'bill_number',
        'amount',
        'issue_date',
        'payment_method',
        'description',
        'pdf_path',
        'created_by_user_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the patient associated with the bill.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor associated with the bill.
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }

    /**
     * Get the user who created this bill.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the items for this bill.
     */
    public function items()
    {
        return $this->hasMany(BillItem::class);
    }
}