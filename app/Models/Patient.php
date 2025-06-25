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
     * Get vital signs for the patient
     */
    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    /**
     * Get medications (prescriptions) for the patient - DIRECT RELATIONSHIP
     */
    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    /**
     * Get medical histories for the patient
     */
    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class);
    }

    /**
     * Get lab results for the patient
     */
    public function labResults(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    /**
     * Get patient notes
     */
    public function patientNotes(): HasMany
    {
        return $this->hasMany(PatientNote::class);
    }

    /**
     * Get patient alerts
     */
    public function patientAlerts(): HasMany
    {
        return $this->hasMany(PatientAlert::class);
    }

    /**
     * Get timeline events for the patient
     */
    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    /**
     * Get patient files
     */
    public function patientFiles(): HasMany
    {
        return $this->hasMany(PatientFile::class);
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

    /**
     * Get comprehensive patient summary for dashboard
     */
    public function getPatientSummary(): array
    {
        // Load necessary relationships
        $this->load([
            'personalInfo',
            'vitalSigns' => function($query) {
                $query->latest()->limit(1);
            },
            'medications' => function($query) {
                $query->where('status', 'active')->latest();
            },
            'medicalHistories',
            'labResults' => function($query) {
                $query->latest()->limit(2);
            },
            'patientNotes' => function($query) {
                $query->where('is_private', false)->latest()->limit(2);
            },
            'patientAlerts' => function($query) {
                $query->where('is_active', true)->orderBy('severity', 'desc');
            },
            'timelineEvents' => function($query) {
                $query->where('is_visible_to_patient', true)->latest()->limit(3);
            },
            'patientFiles' => function($query) {
                $query->latest()->limit(2);
            }
        ]);

        // Get upcoming and recent appointments
        $upcomingAppointments = $this->upcomingAppointments()->limit(5)->get();
        $recentAppointments = $this->appointmentHistory()->limit(5)->get();

        // Calculate statistics
        $stats = $this->calculatePatientStats();

        return [
            // Basic patient information
            'basic_info' => [
                'id' => $this->id,
                'user_id' => $this->user_id,
                'full_name' => $this->full_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'age' => $this->age,
                'gender' => $this->gender,
                'registration_date' => $this->registration_date->format('Y-m-d'),
            ],

            // Statistics overview
            'statistics' => $stats,

            // Recent vital signs
            'recent_vitals' => $this->vitalSigns->map(function($vital) {
                return $vital->toFrontendFormat();
            }),

            // Active medications
            'active_medications' => $this->medications->map(function($medication) {
                return $medication->toFrontendFormat();
            }),

            // Medical history summary
            'medical_history' => $this->medicalHistories->map(function($history) {
                return $history->toFrontendFormat();
            }),

            // Recent lab results
            'recent_lab_results' => $this->labResults->map(function($result) {
                return $result->toFrontendFormat();
            }),

            // Active alerts
            'active_alerts' => $this->patientAlerts->map(function($alert) {
                return $alert->toFrontendFormat();
            }),

            // Recent notes (non-private)
            'recent_notes' => $this->patientNotes->map(function($note) {
                return $note->toFrontendFormat();
            }),

            // Timeline events
            'timeline_events' => $this->timelineEvents->map(function($event) {
                return $event->toFrontendFormat();
            }),

            // Recent files
            'recent_files' => $this->patientFiles->map(function($file) {
                return $file->toFrontendFormat();
            }),

            // Appointments
            'appointments' => [
                'upcoming' => $upcomingAppointments->map(function($appointment) {
                    return [
                        'id' => $appointment->id,
                        'date' => $appointment->appointment_datetime_start,
                        'doctor' => $appointment->doctor->user->name ?? 'Unknown Doctor',
                        'status' => $appointment->status,
                        'type' => $appointment->appointment_type ?? 'consultation'
                    ];
                }),
                'recent' => $recentAppointments->map(function($appointment) {
                    return [
                        'id' => $appointment->id,
                        'date' => $appointment->appointment_datetime_start,
                        'doctor' => $appointment->doctor->user->name ?? 'Unknown Doctor',
                        'status' => $appointment->status,
                        'type' => $appointment->appointment_type ?? 'consultation'
                    ];
                })
            ]
        ];
    }

    /**
     * Calculate patient statistics
     */
    public function calculatePatientStats(): array
    {
        return [
            'total_appointments' => $this->appointments()->count(),
            'upcoming_appointments' => $this->upcomingAppointments()->count(),
            'active_medications' => $this->medications()->where('status', 'active')->count(),
            'active_alerts' => $this->patientAlerts()->where('is_active', true)->count(),
            'total_files' => $this->patientFiles()->count(),
            'recent_vitals_count' => $this->vitalSigns()->where('recorded_at', '>=', now()->subDays(30))->count(),
            'lab_results_this_year' => $this->labResults()->whereYear('result_date', now()->year)->count(),
            'last_visit' => optional($this->appointmentHistory()->first())->appointment_datetime_start,
            'next_appointment' => optional($this->nextAppointment())->appointment_datetime_start,
        ];
    }
}