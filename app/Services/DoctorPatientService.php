<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use App\Models\Appointment;
use App\Models\PatientAlert;
use App\Models\VitalSign;
use App\Models\Medication;
use App\Models\LabResult;
use App\Models\PatientNote;
use App\Services\Medical\PatientMedicalDataService;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class DoctorPatientService
{
    protected $patientMedicalService;    public function __construct(PatientMedicalDataService $patientMedicalService)
    {
        $this->patientMedicalService = $patientMedicalService;
    }

    /**
     * Get patients assigned to a doctor
     */
    public function getDoctorPatients(int $doctorId, array $filters = []): LengthAwarePaginator
    {
        // Get patients who have had appointments with this doctor
        $patientUserIds = Appointment::where('doctor_user_id', $doctorId)
                                   ->distinct()
                                   ->pluck('patient_user_id');

        if ($patientUserIds->isEmpty()) {
            // Return empty paginator if no patients found
            return new LengthAwarePaginator(
                collect([]), 
                0, 
                $filters['limit'] ?? 15, 
                $filters['page'] ?? 1
            );
        }

        $query = Patient::query()
            ->whereIn('user_id', $patientUserIds)
            ->with([
                'user:id,name,email,phone,status',
                'personalInfo:patient_id,name,surname,birthdate,gender'
            ])
            ->withCount([
                'appointments as total_appointments' => function ($query) use ($doctorId) {
                    $query->where('doctor_user_id', $doctorId);
                },
                'appointments as upcoming_appointments' => function ($query) use ($doctorId) {
                    $query->where('doctor_user_id', $doctorId)
                          ->where('appointment_datetime_start', '>=', now())
                          ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_clinic']);
                }
            ]);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%")
                             ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('personalInfo', function ($personalQuery) use ($search) {
                    $personalQuery->where('name', 'like', "%{$search}%")
                                 ->orWhere('surname', 'like', "%{$search}%");
                });
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->whereHas('user', function ($userQuery) use ($filters) {
                $userQuery->where('status', $filters['status']);
            });
        }        // Add critical alerts count
        $query->withCount([
            'patientAlerts as critical_alerts_count' => function ($query) {
                $query->where('severity', 'critical')
                      ->where('is_active', true);
            }
        ]);

        $limit = $filters['limit'] ?? 15;
        $page = $filters['page'] ?? 1;        return $query->orderByDesc('id') // Most recent patients first
                    ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Get comprehensive patient summary for doctor
     */
    public function getPatientSummaryForDoctor(int $doctorId, int $patientId): array
    {
        // Verify doctor has had appointments with this patient
        $hasAppointments = Appointment::where('doctor_user_id', $doctorId)
                                    ->where('patient_user_id', Patient::find($patientId)->user_id)
                                    ->exists();

        if (!$hasAppointments) {
            throw new \Exception('You do not have access to this patient');
        }

        $patient = Patient::with([
            'user',
            'personalInfo',
            'vitalSigns' => function ($query) {
                $query->latest()->limit(5);
            },
            'medications' => function ($query) {
                $query->where('status', 'active');
            },
            'medicalHistories',            'patientAlerts' => function ($query) {
                $query->where('is_active', true)->orderBy('severity', 'desc');
            }
        ])->findOrFail($patientId);

        // Get recent appointments between this doctor and patient
        $recentAppointments = Appointment::where('patient_user_id', $patient->user_id)
                                       ->where('doctor_user_id', $doctorId)
                                       ->where('appointment_datetime_start', '>=', now()->subDays(30))
                                       ->orderBy('appointment_datetime_start', 'desc')
                                       ->limit(5)
                                       ->get();

        // Get recent lab results
        $recentLabResults = LabResult::where('patient_id', $patientId)
                                   ->where('result_date', '>=', now()->subDays(90))
                                   ->orderBy('result_date', 'desc')
                                   ->limit(5)
                                   ->get();

        // Get recent notes from this doctor
        $recentNotes = PatientNote::where('patient_id', $patientId)
                                 ->where('doctor_id', $doctorId)
                                 ->where('created_at', '>=', now()->subDays(30))
                                 ->orderBy('created_at', 'desc')
                                 ->limit(3)
                                 ->get();

        // Calculate risk assessment
        $riskAssessment = $this->calculatePatientRiskAssessment($patient);

        return [
            'patient' => $patient,
            'risk_assessment' => $riskAssessment,
            'recent_appointments' => $recentAppointments,
            'recent_lab_results' => $recentLabResults,
            'recent_notes' => $recentNotes,
            'vital_signs_trend' => $this->getVitalSignsTrend($patientId),
            'medication_adherence' => $this->getMedicationAdherence($patientId),            'upcoming_appointments' => $this->getUpcomingAppointments($doctorId, $patient->user_id)
        ];
    }

    /**
     * Get critical alerts for doctor's patients
     */
    public function getCriticalAlertsForDoctor(int $doctorId, array $filters = []): Collection
    {
        // Get patient user IDs who have appointments with this doctor
        $patientUserIds = Appointment::where('doctor_user_id', $doctorId)
                                   ->distinct()
                                   ->pluck('patient_user_id');

        if ($patientUserIds->isEmpty()) {
            return collect([]);
        }

        // Get patient IDs from user IDs
        $patientIds = Patient::whereIn('user_id', $patientUserIds)->pluck('id');        $query = PatientAlert::query()
            ->select(['id', 'patient_id', 'alert_type', 'severity', 'description', 'created_at'])
            ->whereIn('patient_id', $patientIds)
            ->with(['patient.user:id,name', 'patient.personalInfo:patient_id,name,surname'])
            ->where('is_active', true);

        // Apply severity filter
        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        // Apply type filter
        if (!empty($filters['type'])) {
            $query->where('alert_type', $filters['type']);
        }        $limit = $filters['limit'] ?? 20;

        // PostgreSQL compatible ordering by severity
        return $query->orderByRaw("CASE 
                        WHEN severity = 'critical' THEN 1 
                        WHEN severity = 'high' THEN 2 
                        WHEN severity = 'medium' THEN 3 
                        WHEN severity = 'low' THEN 4 
                        ELSE 5 
                    END")
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)                    ->get();
    }

    /**
     * Get doctor dashboard statistics
     */
    public function getDoctorDashboardStats(int $doctorId): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Get patient user IDs who have appointments with this doctor
        $patientUserIds = Appointment::where('doctor_user_id', $doctorId)
                                   ->distinct()
                                   ->pluck('patient_user_id');

        // Total patients who have had appointments with this doctor
        $totalPatients = $patientUserIds->count();

        // Today's appointments
        $todaysAppointments = Appointment::where('doctor_user_id', $doctorId)
                                       ->whereDate('appointment_datetime_start', $today)
                                       ->count();

        // This week's appointments
        $weeklyAppointments = Appointment::where('doctor_user_id', $doctorId)
                                       ->where('appointment_datetime_start', '>=', $thisWeek)
                                       ->count();

        // Critical alerts for this doctor's patients
        $patientIds = Patient::whereIn('user_id', $patientUserIds)->pluck('id');        $criticalAlerts = PatientAlert::whereIn('patient_id', $patientIds)
                                 ->where('severity', 'critical')
                                 ->where('is_active', true)
                                 ->count();

        // Pending lab results for this doctor's patients
        $pendingLabResults = LabResult::whereIn('patient_id', $patientIds)
                                 ->where('status', 'pending')
                                 ->count();

        // Recent patient activity (vital signs today)
        $recentActivity = VitalSign::whereIn('patient_id', $patientIds)
                               ->whereDate('created_at', $today)
                               ->count();

        return [
            'total_patients' => $totalPatients,
            'todays_appointments' => $todaysAppointments,
            'weekly_appointments' => $weeklyAppointments,
            'critical_alerts' => $criticalAlerts,
            'pending_lab_results' => $pendingLabResults,
            'recent_activity' => $recentActivity,            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Get recent patient activity for doctor
     */
    public function getRecentPatientActivity(int $doctorId, int $limit = 10): Collection
    {
        // Get patient IDs who have appointments with this doctor
        $patientUserIds = Appointment::where('doctor_user_id', $doctorId)
                                   ->distinct()
                                   ->pluck('patient_user_id');

        if ($patientUserIds->isEmpty()) {
            return collect([]);
        }

        $patientIds = Patient::whereIn('user_id', $patientUserIds)->pluck('id');
        $activities = collect();

        // Recent vital signs
        $recentVitals = VitalSign::whereIn('patient_id', $patientIds)
                               ->with(['patient.user:id,name', 'patient.personalInfo:patient_id,name,surname'])
                               ->where('created_at', '>=', now()->subDays(7))
                               ->orderBy('created_at', 'desc')
                               ->limit($limit)
                               ->get()
                               ->map(function ($vital) {
                                   return [
                                       'type' => 'vital_signs',
                                       'patient' => $vital->patient,
                                       'data' => $vital,
                                       'timestamp' => $vital->created_at,
                                       'description' => 'New vital signs recorded'
                                   ];
                               });

        // Recent lab results
        $recentLabs = LabResult::whereIn('patient_id', $patientIds)
                            ->with(['patient.user:id,name', 'patient.personalInfo:patient_id,name,surname'])
                            ->where('result_date', '>=', now()->subDays(7))
                            ->orderBy('result_date', 'desc')
                            ->limit($limit)
                            ->get()
                            ->map(function ($lab) {
                                return [
                                    'type' => 'lab_results',
                                    'patient' => $lab->patient,
                                    'data' => $lab,
                                    'timestamp' => $lab->result_date,
                                    'description' => 'New lab results available'
                                ];
                            });

        $activities = $activities->concat($recentVitals)->concat($recentLabs);        return $activities->sortByDesc('timestamp')->take($limit)->values();
    }

    /**
     * Search patients for doctor
     */
    public function searchDoctorPatients(int $doctorId, string $query, int $limit = 20): Collection
    {
        // Get patient user IDs who have appointments with this doctor
        $patientUserIds = Appointment::where('doctor_user_id', $doctorId)
                                   ->distinct()
                                   ->pluck('patient_user_id');

        if ($patientUserIds->isEmpty()) {
            return collect([]);
        }

        return Patient::whereIn('user_id', $patientUserIds)
                     ->where(function ($q) use ($query) {
                         $q->whereHas('user', function ($userQuery) use ($query) {
                             $userQuery->where('name', 'like', "%{$query}%")
                                      ->orWhere('email', 'like', "%{$query}%")
                                      ->orWhere('phone', 'like', "%{$query}%");
                         })
                         ->orWhereHas('personalInfo', function ($personalQuery) use ($query) {
                             $personalQuery->where('name', 'like', "%{$query}%")
                                          ->orWhere('surname', 'like', "%{$query}%");
                         });
                     })
                     ->with(['user:id,name,email,phone', 'personalInfo:patient_id,name,surname,birthdate'])
                     ->limit($limit)
                     ->get();
    }

    /**
     * Calculate patient risk assessment
     */
    private function calculatePatientRiskAssessment(Patient $patient): array
    {
        $riskFactors = [];
        $riskScore = 0;        // Age factor
        $age = 0;
        if ($patient->personalInfo && $patient->personalInfo->birthdate) {
            $age = Carbon::parse($patient->personalInfo->birthdate)->age;
        }
        
        if ($age > 65) {
            $riskFactors[] = 'Advanced age (>65)';
            $riskScore += 2;
        } elseif ($age > 50) {
            $riskFactors[] = 'Middle age (50-65)';
            $riskScore += 1;
        }

        // Critical alerts
        if ($patient->patientAlerts && $patient->patientAlerts->where('severity', 'critical')->count() > 0) {
            $riskFactors[] = 'Critical health alerts';
            $riskScore += 3;
        }

        // Blood pressure (if available)
        if ($patient->vitalSigns && $patient->vitalSigns->count() > 0) {
            $vitals = $patient->vitalSigns->first();
            if ($vitals->blood_pressure_systolic > 140 || $vitals->blood_pressure_diastolic > 90) {
                $riskFactors[] = 'High blood pressure';
                $riskScore += 2;
            }
        }

        // Multiple medications
        if ($patient->medications && $patient->medications->count() > 5) {
            $riskFactors[] = 'Multiple medications (>5)';
            $riskScore += 1;
        }

        // Determine risk level
        $riskLevel = match (true) {
            $riskScore >= 6 => 'high',
            $riskScore >= 3 => 'medium',
            default => 'low'
        };

        return [
            'score' => $riskScore,
            'level' => $riskLevel,
            'factors' => $riskFactors
        ];
    }

    /**
     * Get vital signs trend for patient
     */
    private function getVitalSignsTrend(int $patientId): array
    {
        $vitals = VitalSign::where('patient_id', $patientId)
                          ->where('created_at', '>=', now()->subDays(30))
                          ->orderBy('created_at', 'desc')
                          ->limit(10)
                          ->get();

        if ($vitals->count() < 2) {
            return ['trend' => 'insufficient_data'];
        }

        $latest = $vitals->first();
        $previous = $vitals->skip(1)->first();

        $trends = [];

        // Blood pressure trend
        if ($latest->blood_pressure_systolic && $previous->blood_pressure_systolic) {
            $diff = $latest->blood_pressure_systolic - $previous->blood_pressure_systolic;
            $trends['blood_pressure'] = $diff > 5 ? 'increasing' : ($diff < -5 ? 'decreasing' : 'stable');
        }

        // Heart rate trend
        if ($latest->heart_rate && $previous->heart_rate) {
            $diff = $latest->heart_rate - $previous->heart_rate;
            $trends['heart_rate'] = $diff > 10 ? 'increasing' : ($diff < -10 ? 'decreasing' : 'stable');
        }

        return $trends;
    }

    /**
     * Get medication adherence data
     */
    private function getMedicationAdherence(int $patientId): array
    {
        $activeMedications = Medication::where('patient_id', $patientId)
                                     ->where('status', 'active')
                                     ->count();

        // This is a simplified adherence calculation
        // In a real system, you'd track actual medication taking vs prescribed
        return [
            'active_medications' => $activeMedications,
            'adherence_rate' => 85, // Placeholder - would be calculated from actual data            'status' => $activeMedications > 0 ? 'monitored' : 'no_medications'
        ];
    }

    /**
     * Get upcoming appointments for patient with doctor
     */
    private function getUpcomingAppointments(int $doctorId, int $patientUserId): Collection
    {
        return Appointment::where('doctor_user_id', $doctorId)
                         ->where('patient_user_id', $patientUserId)
                         ->where('appointment_datetime_start', '>=', now())
                         ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_clinic'])
                         ->orderBy('appointment_datetime_start')
                         ->limit(3)
                         ->get();
    }

    /**
     * ✅ NEW: Get gender demographics for doctor's patients
     */
    public function getGenderDemographics(int $doctorId): array
    {
        // Get patients who have had appointments with this doctor
        $patientUserIds = Appointment::where('doctor_user_id', $doctorId)
                                   ->distinct()
                                   ->pluck('patient_user_id');

        if ($patientUserIds->isEmpty()) {
            return [
                'male' => 0,
                'female' => 0,
                'other' => 0,
                'not_specified' => 0,
                'total_patients' => 0
            ];
        }

        // Get gender distribution from personal_infos table (correct table name)
        $genderCounts = Patient::whereIn('user_id', $patientUserIds)
            ->join('personal_infos', 'patients.id', '=', 'personal_infos.patient_id')
            ->selectRaw('COALESCE(LOWER(gender), \'not_specified\') as gender, COUNT(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        return [
            'male' => $genderCounts['male'] ?? 0,
            'female' => $genderCounts['female'] ?? 0,
            'other' => $genderCounts['other'] ?? 0,
            'not_specified' => ($genderCounts['not_specified'] ?? 0) + ($genderCounts[''] ?? 0),
            'total_patients' => array_sum($genderCounts),
            'percentages' => $this->calculatePercentages($genderCounts)
        ];
    }

    /**
     * ✅ NEW: Get age demographics for doctor's patients
     */
    public function getAgeDemographics(int $doctorId): array
    {
        // Get patients who have had appointments with this doctor
        $patientUserIds = Appointment::where('doctor_user_id', $doctorId)
                                   ->distinct()
                                   ->pluck('patient_user_id');

        if ($patientUserIds->isEmpty()) {
            return [
                'age_groups' => [
                    '0-17' => 0,
                    '18-30' => 0,
                    '31-45' => 0,
                    '46-60' => 0,
                    '61-75' => 0,
                    '76+' => 0
                ],
                'total_patients' => 0,
                'average_age' => 0
            ];
        }

        // Get patients with birthdate from personal_infos table (correct table name)
        $patients = Patient::whereIn('user_id', $patientUserIds)
            ->join('personal_infos', 'patients.id', '=', 'personal_infos.patient_id')
            ->whereNotNull('personal_infos.birthdate')
            ->select('personal_infos.birthdate')
            ->get();

        $ageGroups = [
            '0-17' => 0,
            '18-30' => 0,
            '31-45' => 0,
            '46-60' => 0,
            '61-75' => 0,
            '76+' => 0
        ];

        $totalAge = 0;
        $validPatients = 0;

        foreach ($patients as $patient) {
            $age = Carbon::parse($patient->birthdate)->age;
            $totalAge += $age;
            $validPatients++;

            if ($age <= 17) {
                $ageGroups['0-17']++;
            } elseif ($age <= 30) {
                $ageGroups['18-30']++;
            } elseif ($age <= 45) {
                $ageGroups['31-45']++;
            } elseif ($age <= 60) {
                $ageGroups['46-60']++;
            } elseif ($age <= 75) {
                $ageGroups['61-75']++;
            } else {
                $ageGroups['76+']++;
            }
        }

        return [
            'age_groups' => $ageGroups,
            'total_patients' => $validPatients,
            'average_age' => $validPatients > 0 ? round($totalAge / $validPatients, 1) : 0,
            'percentages' => $this->calculatePercentages($ageGroups)
        ];
    }

    /**
     * ✅ NEW: Get complete demographics overview for doctor's patients
     */
    public function getDemographicsOverview(int $doctorId): array
    {
        $genderData = $this->getGenderDemographics($doctorId);
        $ageData = $this->getAgeDemographics($doctorId);

        // Get additional overview stats
        $patientUserIds = Appointment::where('doctor_user_id', $doctorId)
                                   ->distinct()
                                   ->pluck('patient_user_id');

        $newPatientsThisMonth = 0;
        $totalPatients = count($patientUserIds);

        if (!empty($patientUserIds)) {
            $newPatientsThisMonth = Patient::whereIn('user_id', $patientUserIds)
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count();
        }

        return [
            'overview' => [
                'total_patients' => $totalPatients,
                'new_this_month' => $newPatientsThisMonth,
                'growth_rate' => $totalPatients > 0 ? round(($newPatientsThisMonth / $totalPatients) * 100, 1) : 0
            ],
            'gender_distribution' => $genderData,
            'age_distribution' => $ageData,
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Helper method to calculate percentages
     */
    private function calculatePercentages(array $data): array
    {
        $total = array_sum($data);
        if ($total === 0) {
            return array_map(fn() => 0, $data);
        }

        $percentages = [];
        foreach ($data as $key => $value) {
            $percentages[$key] = round(($value / $total) * 100, 1);
        }

        return $percentages;
    }
}
