<?php

namespace App\Services\Medical;

use App\Models\Patient;
use App\Models\VitalSign;
use App\Models\Medication;
use App\Models\PatientNote;
use App\Models\PatientAlert;
use App\Models\LabResult;
use Illuminate\Database\Eloquent\Collection;

class PatientMedicalDataService
{
    /**
     * Get comprehensive patient medical data.
     */
    public function getPatientMedicalData(Patient $patient, bool $isPatientView = false): array
    {
        return [
            'patient_info' => $this->getBasicPatientInfo($patient),
            'vital_signs' => $this->getVitalSigns($patient),
            'medications' => $this->getMedications($patient),
            'medical_history' => $this->getMedicalHistory($patient),
            'lab_results' => $this->getLabResults($patient),
            'notes' => $this->getPatientNotes($patient, ['is_private' => !$isPatientView]),
            'alerts' => $this->getActiveAlerts($patient),
            'statistics' => $this->getPatientStatistics($patient),
        ];
    }

    /**
     * Get basic patient information.
     */
    public function getBasicPatientInfo(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'user_id' => $patient->user_id,
            'full_name' => $patient->full_name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'age' => $patient->age,
            'gender' => $patient->gender,
            'registration_date' => $patient->registration_date->format('Y-m-d'),
        ];
    }

    /**
     * Get patient vital signs with filters.
     */
    public function getPatientVitalSigns(int $patientId, array $filters = []): Collection
    {
        $patient = Patient::findOrFail($patientId);
        
        $query = $patient->vitalSigns()->latest('recorded_at');
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('recorded_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('recorded_at', '<=', $filters['date_to']);
        }
        
        // Apply limit
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get patient vital signs.
     */
    public function getVitalSigns(Patient $patient, int $limit = null): Collection
    {
        $query = $patient->vitalSigns()->latest('recorded_at');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get patient medications with filters.
     */
    public function getPatientMedications(int $patientId, array $filters = []): Collection
    {
        $patient = Patient::findOrFail($patientId);
        
        $query = $patient->medications()->latest();
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        // Apply limit
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get patient medications.
     */
    public function getMedications(Patient $patient, string $status = null): Collection
    {
        $query = $patient->medications()->latest();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->get();
    }

    /**
     * Get patient medical history with filters.
     */
    public function getPatientMedicalHistory(int $patientId, array $filters = []): Collection
    {
        $patient = Patient::findOrFail($patientId);
        
        $query = $patient->medicalHistories()->latest();
        
        // Apply limit
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get patient medical history.
     */
    public function getMedicalHistory(Patient $patient): Collection
    {
        return $patient->medicalHistories()->latest()->get();
    }

    /**
     * Get patient lab results with filters.
     */
    public function getPatientLabResults(int $patientId, array $filters = []): Collection
    {
        $patient = Patient::findOrFail($patientId);
        
        $query = $patient->labResults()->latest('result_date');
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('result_date', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('result_date', '<=', $filters['date_to']);
        }
        
        // Apply limit
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get patient lab results.
     */
    public function getLabResults(Patient $patient, int $limit = null): Collection
    {
        $query = $patient->labResults()->latest('result_date');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get patient timeline with filters.
     */
    public function getPatientTimeline(int $patientId, array $filters = []): Collection
    {
        $patient = Patient::findOrFail($patientId);
        
        $query = $patient->timelineEvents()->latest();
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        // Apply limit
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get patient files with filters.
     */
    public function getPatientFiles(int $patientId, array $filters = []): Collection
    {
        $patient = Patient::findOrFail($patientId);
        
        $query = $patient->patientFiles()->latest('uploaded_at');
        
        // Apply file type filter
        if (!empty($filters['file_type'])) {
            $query->where('file_type', $filters['file_type']);
        }
        
        // Apply category filter
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('uploaded_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('uploaded_at', '<=', $filters['date_to']);
        }
        
        // Apply limit
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get patient notes with filters.
     */
    public function getPatientNotes($patient, array $filters = []): Collection
    {
        // Handle both Patient object and patient ID
        if (is_numeric($patient)) {
            $patient = Patient::findOrFail($patient);
        }
        
        $query = $patient->patientNotes()->latest();
        
        // Legacy support: if filters is boolean, it's isPatientView
        if (is_bool($filters)) {
            if ($filters) {
                $query->where('is_private', false);
            }
            return $query->get();
        }
        
        // Apply privacy filter
        if (isset($filters['is_private'])) {
            $query->where('is_private', $filters['is_private']);
        }
        
        // Apply note type filter
        if (!empty($filters['note_type'])) {
            $query->where('note_type', $filters['note_type']);
        }
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        // Apply limit
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get patient alerts with filters.
     */
    public function getPatientAlerts(int $patientId, array $filters = []): Collection
    {
        $patient = Patient::findOrFail($patientId);
        
        $query = $patient->patientAlerts()->latest();
        
        // Apply active filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        // Apply severity filter
        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        
        // Apply alert type filter
        if (!empty($filters['alert_type'])) {
            $query->where('alert_type', $filters['alert_type']);
        }
        
        // Apply limit
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get active patient alerts.
     */
    public function getActiveAlerts(Patient $patient): Collection
    {
        return $patient->patientAlerts()
            ->where('is_active', true)
            ->orderBy('severity', 'desc')
            ->get();
    }

    /**
     * Get patient medical statistics.
     */
    public function getPatientStatistics(Patient $patient): array
    {
        return [
            'active_medications' => $patient->medications()->where('status', 'active')->count(),
            'active_alerts' => $patient->patientAlerts()->where('is_active', true)->count(),
            'recent_vitals_count' => $patient->vitalSigns()->where('recorded_at', '>=', now()->subDays(30))->count(),
            'lab_results_this_year' => $patient->labResults()->whereYear('result_date', now()->year)->count(),
            'total_notes' => $patient->patientNotes()->count(),
            'private_notes' => $patient->patientNotes()->where('is_private', true)->count(),
        ];
    }

    /**
     * Create a new vital sign record.
     */
    public function createVitalSign(Patient $patient, array $data): VitalSign
    {
        $data['patient_id'] = $patient->id;
        $data['recorded_at'] = $data['recorded_at'] ?? now();
        
        return VitalSign::create($data);
    }

    /**
     * Create a new patient note.
     */
    public function createPatientNote(Patient $patient, array $data): PatientNote
    {
        $data['patient_id'] = $patient->id;
        
        return PatientNote::create($data);
    }

    /**
     * Create a new patient alert.
     */
    public function createPatientAlert(Patient $patient, array $data): PatientAlert
    {
        $data['patient_id'] = $patient->id;
        
        return PatientAlert::create($data);
    }
}
