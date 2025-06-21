<?php

namespace App\Services;

use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Services\Medical\TimelineEventService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MedicalHistoryService
{
    protected TimelineEventService $timelineService;

    public function __construct(TimelineEventService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    /**
     * Get all medical histories for a patient.
     */
    public function getPatientMedicalHistories(int $patientId): Collection
    {
        $patient = Patient::findOrFail($patientId);
        
        return $patient->medicalHistories()
            ->with('updatedBy:id,name')
            ->latest('last_updated')
            ->get()
            ->map(function ($history) {
                return $history->toFrontendFormat();
            });
    }

    /**
     * Create a new medical history record.
     */
    public function createMedicalHistory(int $patientId, array $data): MedicalHistory
    {
        return DB::transaction(function () use ($patientId, $data) {
            $patient = Patient::findOrFail($patientId);

            $medicalHistory = new MedicalHistory();
            $medicalHistory->patient_id = $patientId;
            $medicalHistory->current_medical_conditions = $data['current_medical_conditions'] ?? [];
            $medicalHistory->past_surgeries = $data['past_surgeries'] ?? [];
            $medicalHistory->chronic_diseases = $data['chronic_diseases'] ?? [];
            $medicalHistory->current_medications = $data['current_medications'] ?? [];
            $medicalHistory->allergies = $data['allergies'] ?? [];
            $medicalHistory->updated_by_user_id = $data['updated_by_user_id'];
            $medicalHistory->last_updated = now();
            
            $medicalHistory->save();

            // Create timeline event - FIX: Use createTimelineEvent method
            $this->timelineService->createTimelineEvent(
                $patient,
                'note', // Use 'note' instead of 'medical_record_updated'
                'Medical History Created',
                'New medical history record was created',
                $medicalHistory,
                'medium',
                true,
                $data['updated_by_user_id']
            );

            return $medicalHistory;
        });
    }

    /**
     * Update an existing medical history record.
     */
    public function updateMedicalHistory(int $id, array $data): MedicalHistory
    {
        return DB::transaction(function () use ($id, $data) {
            $medicalHistory = MedicalHistory::findOrFail($id);
            
            // Helper function to ensure array format
            $ensureArray = function ($value) {
                if (is_string($value)) {
                    if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                        $decoded = json_decode($value, true);
                        return is_array($decoded) ? $decoded : [];
                    }
                    return array_filter(array_map('trim', explode(',', $value)));
                }
                return is_array($value) ? $value : [];
            };

            // Store old data for comparison - ensure arrays
            $oldData = [
                'conditions' => $ensureArray($medicalHistory->current_medical_conditions ?? []),
                'allergies' => $ensureArray($medicalHistory->allergies ?? []),
                'medications' => $ensureArray($medicalHistory->current_medications ?? []),
            ];

            // Update fields - ensure incoming data is arrays
            $medicalHistory->current_medical_conditions = $ensureArray($data['current_medical_conditions'] ?? []);
            $medicalHistory->past_surgeries = $ensureArray($data['past_surgeries'] ?? []);
            $medicalHistory->chronic_diseases = $ensureArray($data['chronic_diseases'] ?? []);
            $medicalHistory->current_medications = $ensureArray($data['current_medications'] ?? []);
            $medicalHistory->allergies = $ensureArray($data['allergies'] ?? []);
            $medicalHistory->updated_by_user_id = $data['updated_by_user_id'];
            $medicalHistory->last_updated = now();
            
            $medicalHistory->save();

            // Create timeline event for significant changes
            $changes = $this->detectSignificantChanges($oldData, [
                'conditions' => $medicalHistory->current_medical_conditions ?? [],
                'allergies' => $medicalHistory->allergies ?? [],
                'medications' => $medicalHistory->current_medications ?? [],
            ]);

            if (!empty($changes)) {
                $this->timelineService->createTimelineEvent(
                    Patient::find($medicalHistory->patient_id),
                    'note',
                    'Medical History Updated',
                    'Medical history record was updated with significant changes',
                    $medicalHistory,
                    'medium',
                    true,
                    $data['updated_by_user_id']
                );
            }

            return $medicalHistory;
        });
    }

    /**
     * Delete a medical history record.
     */
public function deleteMedicalHistory(int $id, int $userId): bool
{
    return DB::transaction(function () use ($id, $userId) {
        try {
            $medicalHistory = MedicalHistory::findOrFail($id);
            $patient = Patient::find($medicalHistory->patient_id);
            
            // Validate that the medical history belongs to the patient
            if (!$patient) {
                throw new \Exception('Patient not found for this medical history');
            }
            
            // Create timeline event before deletion (optional)
            try {
                $this->timelineService->createTimelineEvent(
                    $patient,
                    'note',
                    'Medical History Deleted',
                    'Medical history record was deleted',
                    null,
                    'medium',
                    true,
                    $userId
                );
            } catch (\Exception $e) {
                // Log timeline error but don't fail the deletion
                \Log::warning('Failed to create timeline event for medical history deletion', [
                    'error' => $e->getMessage(),
                    'medical_history_id' => $id,
                    'patient_id' => $medicalHistory->patient_id
                ]);
            }

            // Delete the medical history record
            return $medicalHistory->delete();
            
        } catch (\Exception $e) {
            \Log::error('Error deleting medical history', [
                'error' => $e->getMessage(),
                'medical_history_id' => $id,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    });
}

    /**
     * Detect significant changes in medical history.
     */
    private function detectSignificantChanges(array $oldData, array $newData): array
    {
        $changes = [];

        // Helper function to ensure array format
        $ensureArray = function ($value) {
            if (is_string($value)) {
                // If it's a JSON string, decode it
                if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : [];
                }
                // If it's a comma-separated string
                return array_filter(array_map('trim', explode(',', $value)));
            }
            return is_array($value) ? $value : [];
        };

        // Ensure all data is in array format
        $oldAllergies = $ensureArray($oldData['allergies'] ?? []);
        $newAllergies = $ensureArray($newData['allergies'] ?? []);
        $oldConditions = $ensureArray($oldData['conditions'] ?? []);
        $newConditions = $ensureArray($newData['conditions'] ?? []);
        $oldMedications = $ensureArray($oldData['medications'] ?? []);
        $newMedications = $ensureArray($newData['medications'] ?? []);

        // Check for new allergies (critical for safety)
        $addedAllergies = array_diff($newAllergies, $oldAllergies);
        if (!empty($addedAllergies)) {
            $changes['new_allergies'] = $addedAllergies;
        }

        // Check for new conditions
        $addedConditions = array_diff($newConditions, $oldConditions);
        if (!empty($addedConditions)) {
            $changes['new_conditions'] = $addedConditions;
        }

        // Check for new medications
        $addedMedications = array_diff($newMedications, $oldMedications);
        if (!empty($addedMedications)) {
            $changes['new_medications'] = $addedMedications;
        }

        return $changes;
    }

    /**
     * Get medical history summary for a patient.
     */
    public function getMedicalHistorySummary(int $patientId): array
    {
        $patient = Patient::findOrFail($patientId);
        $latestHistory = $patient->medicalHistories()->latest('last_updated')->first();

        if (!$latestHistory) {
            return [
                'has_history' => false,
                'summary' => 'No medical history available'
            ];
        }

        return [
            'has_history' => true,
            'last_updated' => $latestHistory->last_updated?->toISOString(),
            'conditions_count' => count($latestHistory->current_medical_conditions ?? []),
            'allergies_count' => count($latestHistory->allergies ?? []),
            'medications_count' => count($latestHistory->current_medications ?? []),
            'critical_allergies' => $latestHistory->allergies ?? [],
            'chronic_conditions' => $latestHistory->chronic_diseases ?? [],
        ];
    }
}