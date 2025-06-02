<?php
// filepath: c:\Users\Sefanos\Desktop\n8n\Frontend\admin_backend\app\Models\Appointment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Appointment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'patient_user_id',
        'doctor_user_id', 
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
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'appointment_datetime_start',
        'appointment_datetime_end',
        'reminder_sent_at',
        'deleted_at',
    ];

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED_BY_PATIENT = 'cancelled_by_patient';
    const STATUS_CANCELLED_BY_CLINIC = 'cancelled_by_clinic';
    const STATUS_NO_SHOW = 'no_show';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED_BY_PATIENT,
            self::STATUS_CANCELLED_BY_CLINIC,
            self::STATUS_NO_SHOW,
        ];
    }

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by_user_id');
    }

    // Scopes
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_user_id', $patientId);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_user_id', $doctorId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_datetime_start', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('appointment_datetime_start', '<', now());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_datetime_start', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('appointment_datetime_start', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    // Accessors & Mutators
    public function getDateAttribute()
    {
        return $this->appointment_datetime_start?->format('Y-m-d');
    }

    public function getTimeAttribute()
    {
        return $this->appointment_datetime_start?->format('H:i');
    }

    public function getDurationAttribute()
    {
        if ($this->appointment_datetime_start && $this->appointment_datetime_end) {
            return $this->appointment_datetime_start->diffInMinutes($this->appointment_datetime_end);
        }
        return 30; // Default 30 minutes
    }

    // Business Logic Methods
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED
        ]) && $this->appointment_datetime_start > now()->addHours(2);
    }

    public function canBeRescheduled(): bool
    {
        return in_array($this->status, [
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED
        ]) && $this->appointment_datetime_start > now()->addHours(24);
    }

    public function isPast(): bool
    {
        return $this->appointment_datetime_start < now();
    }

    public function isToday(): bool
    {
        return $this->appointment_datetime_start->isToday();
    }

    public function isUpcoming(): bool
    {
        return $this->appointment_datetime_start > now();
    }

    public function cancel(string $reason, User $cancelledBy): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $statusMap = [
            'patient' => self::STATUS_CANCELLED_BY_PATIENT,
            'doctor' => self::STATUS_CANCELLED_BY_CLINIC,
            'receptionist' => self::STATUS_CANCELLED_BY_CLINIC,
            'admin' => self::STATUS_CANCELLED_BY_CLINIC,
        ];

        $userRole = $cancelledBy->roles->first()?->code ?? 'patient';
        
        $this->update([
            'status' => $statusMap[$userRole] ?? self::STATUS_CANCELLED_BY_CLINIC,
            'cancellation_reason' => $reason,
            'last_updated_by_user_id' => $cancelledBy->id,
        ]);

        return true;
    }

    public function confirm(User $confirmedBy): bool
    {
        if ($this->status !== self::STATUS_SCHEDULED) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'last_updated_by_user_id' => $confirmedBy->id,
        ]);

        return true;
    }

    public function complete(User $completedBy, ?string $notes = null): bool
    {
        if (!in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_SCHEDULED])) {
            return false;
        }

        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'last_updated_by_user_id' => $completedBy->id,
        ];

        if ($notes) {
            $updateData['notes_by_staff'] = $notes;
        }

        $this->update($updateData);

        return true;
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'patient_user_id',
                'doctor_user_id',
                'appointment_datetime_start',
                'appointment_datetime_end',
                'type',
                'reason_for_visit',
                'status',
                'cancellation_reason'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // For frontend compatibility
    public function toCalendarEvent(): array
    {
        return [
            'id' => $this->id,
            'title' => "{$this->reason_for_visit} - {$this->doctor->name}",
            'start' => $this->appointment_datetime_start->toISOString(),
            'end' => $this->appointment_datetime_end->toISOString(),
            'backgroundColor' => $this->getStatusColor(),
            'borderColor' => $this->getStatusColor(),
            'textColor' => '#ffffff',
            'extendedProps' => [
                'appointmentId' => $this->id,
                'patientName' => $this->patient->name,
                'doctorName' => $this->doctor->name,
                'doctorSpecialty' => $this->doctor->doctorProfile?->specialty ?? 'General Medicine',
                'status' => $this->status,
                'reason' => $this->reason_for_visit,
                'type' => $this->type,
                'notes' => array_filter([
                    $this->notes_by_patient,
                    $this->notes_by_staff
                ]),
                'cancelReason' => $this->cancellation_reason,
            ]
        ];
    }

    private function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_CONFIRMED => '#28a745',
            self::STATUS_SCHEDULED => '#ffc107',
            self::STATUS_COMPLETED => '#6c757d',
            self::STATUS_CANCELLED_BY_PATIENT, 
            self::STATUS_CANCELLED_BY_CLINIC => '#dc3545',
            self::STATUS_NO_SHOW => '#fd7e14',
            default => '#007bff'
        };
    }
}