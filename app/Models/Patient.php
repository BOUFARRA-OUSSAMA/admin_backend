<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'registration_date'
    ];

    protected $casts = [
        'registration_date' => 'date',
    ];

    /**
     * Get the user that owns the patient.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the personal info associated with the patient.
     */
    public function personalInfo(): HasOne
    {
        return $this->hasOne(PersonalInfo::class);
    }

    /**
     * Get the appointments for the patient
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_user_id', 'user_id');
    }

    /**
     * Get upcoming appointments for this patient.
     */
    public function upcomingAppointments()
    {
        return $this->appointments()
            ->with(['doctor']) // ✅ REMOVED: timeSlot from with()
            ->where('appointment_datetime_start', '>=', now())
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->orderBy('appointment_datetime_start');
    }

    /**
     * Get today's appointments for this patient.
     */
    public function todaysAppointments()
    {
        return $this->appointments()
            ->with(['doctor']) // ✅ REMOVED: timeSlot from with()
            ->whereDate('appointment_datetime_start', today())
            ->orderBy('appointment_datetime_start');
    }

    /**
     * Get appointment history for this patient.
     */
    public function appointmentHistory()
    {
        return $this->appointments()
            ->with(['doctor']) // ✅ REMOVED: timeSlot from with()
            ->where('appointment_datetime_start', '<', now())
            ->orderBy('appointment_datetime_start', 'desc');
    }

    /**
     * Check if patient has any upcoming appointments.
     */
    public function hasUpcomingAppointments(): bool
    {
        return $this->upcomingAppointments()->exists();
    }

    /**
     * Get next appointment for this patient.
     */
    public function nextAppointment()
    {
        return $this->upcomingAppointments()->first();
    }

    /**
     * Get patient's full name from personal info or user.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->personalInfo) {
            return $this->personalInfo->name . ' ' . $this->personalInfo->surname;
        }
        
        return $this->user->name ?? 'Unknown Patient';
    }

    /**
     * Get patient's contact email.
     */
    public function getEmailAttribute(): string
    {
        return $this->personalInfo->email ?? $this->user->email ?? '';
    }

    /**
     * Get patient's contact phone.
     */
    public function getPhoneAttribute(): string
    {
        return $this->personalInfo->phone ?? $this->user->phone ?? '';
    }

    /**
     * Get patient's age from PersonalInfo
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->personalInfo && $this->personalInfo->birthdate) {
            return \Carbon\Carbon::parse($this->personalInfo->birthdate)->age;
        }
        
        return null;
    }

    /**
     * Get patient's gender
     */
    public function getGenderAttribute(): ?string
    {
        return $this->personalInfo->gender ?? null;
    }

    /**
     * Scope for active patients
     */
    public function scopeActive($query)
    {
        return $query->whereHas('user', function($q) {
            $q->where('status', 'active');
        });
    }
}