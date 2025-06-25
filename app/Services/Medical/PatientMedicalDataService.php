<?php

namespace App\Services\Medical;

use App\Models\Patient;
use App\Models\VitalSign;
use App\Models\Medication;
use App\Models\PatientNote;
use App\Models\PatientAlert;
use App\Models\LabResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class PatientMedicalDataService
{
    /**
     * Get comprehensive patient medical data.
     */
    public function getPatientMedicalData(Patient $patient, bool $isPatientView = false): array
    {
         try {
             Log::info("Début de getPatientMedicalData pour le patient ID: {$patient->id}");
               $data = [];
               Log::info("Étape 1/8: getBasicPatientInfo");
            $data['patient_info'] = $this->getBasicPatientInfo($patient);

            Log::info("Étape 2/8: getPatientVitalSigns");
            $data['vital_signs'] = $this->getPatientVitalSigns($patient->id, ['limit' => 5]);

            Log::info("Étape 3/8: getPatientMedications");
            $data['medications'] = $this->getPatientMedications($patient->id, ['status' => 'active', 'limit' => 5]);

            Log::info("Étape 4/8: getPatientMedicalHistory");
            $data['medical_history'] = $this->getPatientMedicalHistory($patient->id, ['limit' => 1]);

            Log::info("Étape 5/8: getPatientLabResults");
            $data['lab_results'] = $this->getPatientLabResults($patient->id, ['limit' => 5]);

            Log::info("Étape 6/8: getPatientNotes");
            $data['notes'] = $this->getPatientNotes($patient, ['limit' => 5]);

            Log::info("Étape 7/8: getPatientAlerts");
            $data['alerts'] = $this->getPatientAlerts($patient->id, ['status' => 'active', 'limit' => 5]);

            Log::info("Étape 8/8: getPatientStatistics");
            $data['statistics'] = $this->getPatientStatistics($patient);


        
         Log::info("Fin de getPatientMedicalData avec succès pour le patient ID: {$patient->id}");
            return $data;

        } catch (\Throwable $e) {
            Log::error("ERREUR FATALE dans getPatientMedicalData pour le patient ID: {$patient->id}", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
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
        
        $query = $patient->medications()
                        ->with('doctor:id,name')
                        ->latest();
        
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
        
        $query = $patient->medicalHistories()->latest('last_updated');
        
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
    // public function getPatientLabResults(int $patientId, array $filters = []): Collection
    // {
    //     $patient = Patient::findOrFail($patientId);
        
    //     $query = $patient->labResults()->latest('result_date');
        
    //     // Apply date filters
    //     if (!empty($filters['date_from'])) {
    //         $query->whereDate('result_date', '>=', $filters['date_from']);
    //     }
        
    //     if (!empty($filters['date_to'])) {
    //         $query->whereDate('result_date', '<=', $filters['date_to']);
    //     }
        
    //     // Apply limit
    //     if (!empty($filters['limit'])) {
    //         $query->limit($filters['limit']);
    //     }
        
    //     return $query->get();
    // }

    public function getPatientLabResults(int $patientId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // ✅ MODIFICATION DE LA REQUÊTE
        $query = LabResult::query()
            ->where('patient_id', $patientId)
            // Charger la relation 'reviewedBy' et ne sélectionner que l'ID et le nom pour la performance.
            ->with([
                'reviewedBy:id,name', // Charge le nom du médecin
                'reviewedBy.doctor:user_id,specialty' // Charge la spécialité du médecin
            ]) 
            ->latest('result_date');
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('result_date', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('result_date', '<=', $filters['date_to']);
        }
        
        // La méthode get() est remplacée par paginate() pour gérer le 'limit'
        return $query->paginate($filters['limit'] ?? 20);
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
        
        $query = $patient->patientNotes()
                        ->with('doctor:id,name') 
                        ->latest();
        

        
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

     /**
     * ✅ CORRIGÉ : Calcule les statistiques sur TOUS les historiques médicaux,
     * en comptant uniquement les éléments uniques pour chaque catégorie.
     */
    public function getPatientSummaryStatistics(Patient $patient): array
    {
        try {
            // 1. Récupérer TOUS les historiques médicaux du patient.
            $histories = $patient->medicalHistories;

            // 2. Créer une fonction d'aide pour extraire, aplatir et compter les éléments uniques.
            //    Cela évite la répétition de code.
            $getUniqueCount = function (string $field) use ($histories) {
                return $histories
                    // a. Extraire la colonne (qui contient des chaînes JSON).
                    ->pluck($field)
                    // b. Décoder chaque chaîne JSON en un tableau PHP.

                  ->map(fn($json) => is_array($json) ? $json : json_decode($json, true) ?? [])
                    // c. Aplatir la collection de tableaux en une seule liste.
                    ->flatten()
                    // d. Supprimer tous les doublons de la liste.
                    ->unique()
                    // e. Compter le nombre d'éléments uniques restants.
                    ->count();
            };
             // 3. Appliquer la fonction à chaque champ pour obtenir les statistiques.
            return [
                'conditions' => $getUniqueCount('current_medical_conditions'),
                'chronic' => $getUniqueCount('chronic_diseases'),
                'surgeries' => $getUniqueCount('past_surgeries'),
                'allergies' => $getUniqueCount('allergies'),
                'active_medications' => $getUniqueCount('current_medications'),
            ];

        } catch (\Throwable $e) {
            Log::error("Erreur dans getPatientSummaryStatistics pour le patient ID: " . $patient->id, [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;   
            }
        }
}

