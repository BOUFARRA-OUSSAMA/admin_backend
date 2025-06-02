<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_user_id',
        'doctor_user_id',
        'time_slot_id',
        'appointment_datetime_start',
        'appointment_datetime_end',
        'type',
        'reason_for_visit',
        'status',
        'cancellation_reason',
        'notes_by_patient',
        'notes_by_staff',
        'booked_by_user_id',
        'last_updated_by_user_id',
        'reminder_sent',
        'reminder_sent_at',
        'verification_code'
    ];

    protected $casts = [
        'appointment_datetime_start' => 'datetime',
        'appointment_datetime_end' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    // Relations
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by_user_id');
    }

    // Accessors pour la compatibilité avec le code existant
    public function getDateAttribute()
    {
        return $this->appointment_datetime_start->toDateString();
    }

    public function getReasonAttribute()
    {
        return $this->reason_for_visit;
    }

    public function getCancelReasonAttribute()
    {
        return $this->cancellation_reason;
    }
}