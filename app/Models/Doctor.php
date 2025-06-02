<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Doctor extends Model
{
    protected $fillable = [
        'user_id',
        'license_number',
        'specialty',
        'experience_years',
        'consultation_fee',
        'is_available',
        'working_hours',
        'max_patient_appointments', 
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'working_hours' => 'array',
        'consultation_fee' => 'decimal:2',
        'max_patient_appointments' => 'integer', 
    ];

    /**
     * Get the user that owns the doctor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get doctor's appointments.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_user_id', 'user_id');
    }

    /**
     * Get doctor's blocked time slots.
     */
    public function blockedTimeSlots(): HasMany
    {
        return $this->hasMany(BlockedTimeSlot::class, 'doctor_user_id', 'user_id');
    }

    /**
     * Get upcoming appointments for this doctor.
     */
    public function upcomingAppointments()
    {
        return $this->appointments()
            ->with(['patient']) // ✅ REMOVED: timeSlot from with()
            ->where('appointment_datetime_start', '>=', now())
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->orderBy('appointment_datetime_start');
    }

    /**
     * Get today's appointments for this doctor.
     */
    public function todaysAppointments()
    {
        return $this->appointments()
            ->with(['patient']) // ✅ REMOVED: timeSlot from with()
            ->whereDate('appointment_datetime_start', today())
            ->orderBy('appointment_datetime_start');
    }

    /**
     * Check if doctor is available at given time.
     */
    public function isAvailableAt(Carbon $dateTime): bool
    {
        // Check if doctor is generally available
        if (!$this->is_available) {
            return false;
        }

        // Check working hours
        $dayName = strtolower($dateTime->format('l'));
        $workingHours = $this->working_hours[$dayName] ?? null;
        
        if (!$workingHours) {
            return false; // Doctor doesn't work on this day
        }

        $workStart = Carbon::parse($dateTime->format('Y-m-d') . ' ' . $workingHours[0]);
        $workEnd = Carbon::parse($dateTime->format('Y-m-d') . ' ' . $workingHours[1]);
        
        if ($dateTime < $workStart || $dateTime >= $workEnd) {
            return false; // Outside working hours
        }

        // Check for blocked time slots
        $blocked = $this->blockedTimeSlots()
            ->where('start_datetime', '<=', $dateTime)
            ->where('end_datetime', '>', $dateTime)
            ->exists();

        if ($blocked) {
            return false;
        }

        // Check for existing appointments
        $hasAppointment = $this->appointments()
            ->where('appointment_datetime_start', '<=', $dateTime)
            ->where('appointment_datetime_end', '>', $dateTime)
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->exists();

        return !$hasAppointment;
    }

    /**
     * Get available time slots for a specific date.
     */
    public function getAvailableSlotsForDate(Carbon $date): array
    {
        $dayName = strtolower($date->format('l'));
        $workingHours = $this->working_hours[$dayName] ?? null;
        
        if (!$workingHours) {
            return []; // Doctor doesn't work on this day
        }

        $startTime = $workingHours[0]; // "09:00"
        $endTime = $workingHours[1];   // "17:00"

        // Get existing appointments for this date
        $appointments = $this->appointments()
            ->whereDate('appointment_datetime_start', $date)
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->get();

        // Get blocked time slots for this date
        $blockedSlots = $this->blockedTimeSlots()
            ->whereDate('start_datetime', $date)
            ->get();

        // Generate available slots
        $availableSlots = [];
        $slotDuration = 30; // minutes
        $currentTime = Carbon::parse($date->format('Y-m-d') . ' ' . $startTime);
        $endTimeCarbon = Carbon::parse($date->format('Y-m-d') . ' ' . $endTime);

        // Skip lunch break (12:00-13:00)
        $lunchStart = Carbon::parse($date->format('Y-m-d') . ' 12:00');
        $lunchEnd = Carbon::parse($date->format('Y-m-d') . ' 13:00');

        while ($currentTime < $endTimeCarbon) {
            $slotEnd = $currentTime->copy()->addMinutes($slotDuration);

            // Skip lunch break
            if ($currentTime >= $lunchStart && $currentTime < $lunchEnd) {
                $currentTime = $lunchEnd->copy();
                continue;
            }

            // Check for conflicts
            $isBooked = $appointments->contains(function ($appointment) use ($currentTime, $slotEnd) {
                return $appointment->appointment_datetime_start < $slotEnd && 
                       $appointment->appointment_datetime_end > $currentTime;
            });

            $isBlocked = $blockedSlots->contains(function ($blocked) use ($currentTime, $slotEnd) {
                return $blocked->start_datetime < $slotEnd && 
                       $blocked->end_datetime > $currentTime;
            });

            if (!$isBooked && !$isBlocked && $slotEnd <= $endTimeCarbon) {
                $availableSlots[] = [
                    'start_time' => $currentTime->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'start_datetime' => $currentTime->format('Y-m-d H:i:s'),
                    'end_datetime' => $slotEnd->format('Y-m-d H:i:s'),
                    'duration_minutes' => $slotDuration,
                    'is_available' => true
                ];
            }

            $currentTime->addMinutes($slotDuration);
        }

        return $availableSlots;
    }

    /**
     * Get doctor's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->user->name ?? 'Unknown Doctor';
    }

    /**
     * Get doctor's display name with title.
     */
    public function getDisplayNameAttribute(): string
    {
        return "Dr. " . $this->full_name;
    }

    /**
     * Get doctor's working schedule for a specific day
     */
    public function getWorkingHoursForDay(string $day): ?array
    {
        $dayName = strtolower($day);
        return $this->working_hours[$dayName] ?? null;
    }

    /**
     * Check if doctor works on a specific day
     */
    public function worksOnDay(string $day): bool
    {
        return $this->getWorkingHoursForDay($day) !== null;
    }

    /**
     * Get all working days
     */
    public function getWorkingDaysAttribute(): array
    {
        return array_keys(array_filter($this->working_hours ?? [], function($hours) {
            return $hours !== null;
        }));
    }

    /**
     * Scope for available doctors
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope for doctors by specialty
     */
    public function scopeBySpecialty($query, string $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    /**
     * ✅ NEW: Get max appointments for this doctor
     */
    public function getMaxPatientAppointments(): int
    {
        return $this->max_patient_appointments ?? config('appointments.default_max_upcoming', 5);
    }
}