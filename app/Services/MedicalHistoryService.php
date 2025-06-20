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
            
            // Store old data for comparison
            $oldData = [
                'conditions' => $medicalHistory->current_medical_conditions ?? [],
                'allergies' => $medicalHistory->allergies ?? [],
                'medications' => $medicalHistory->current_medications ?? [],
            ];

            // Update fields
            $medicalHistory->current_medical_conditions = $data['current_medical_conditions'] ?? $medicalHistory->current_medical_conditions;
            $medicalHistory->past_surgeries = $data['past_surgeries'] ?? $medicalHistory->past_surgeries;
            $medicalHistory->chronic_diseases = $data['chronic_diseases'] ?? $medicalHistory->chronic_diseases;
            $medicalHistory->current_medications = $data['current_medications'] ?? $medicalHistory->current_medications;
            $medicalHistory->allergies = $data['allergies'] ?? $medicalHistory->allergies;
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
                // FIX: Use createTimelineEvent method
                $this->timelineService->createTimelineEvent(
                    Patient::find($medicalHistory->patient_id),
                    'note', // Use 'note' instead of 'medical_record_updated'
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
public function deleteMedicalHistory(int $id, int $userId = null): bool
{
    return DB::transaction(function () use ($id, $userId) {
        $medicalHistory = MedicalHistory::findOrFail($id);
        $patient = Patient::find($medicalHistory->patient_id);
        
        // Create timeline event before deletion
        $this->timelineService->createTimelineEvent(
            $patient,
            'note',
            'Medical History Deleted',
            'Medical history record was deleted',
            null,
            'medium',
            true,
            $userId // Pass the user ID instead of null
        );

        return $medicalHistory->delete();
    });
}

    /**
     * Detect significant changes in medical history.
     */
    private function detectSignificantChanges(array $oldData, array $newData): array
    {
        $changes = [];

        // Check for new allergies (critical for safety)
        $newAllergies = array_diff($newData['allergies'], $oldData['allergies']);
        if (!empty($newAllergies)) {
            $changes['new_allergies'] = $newAllergies;
        }

        // Check for new conditions
        $newConditions = array_diff($newData['conditions'], $oldData['conditions']);
        if (!empty($newConditions)) {
            $changes['new_conditions'] = $newConditions;
        }

        // Check for new medications
        $newMedications = array_diff($newData['medications'], $oldData['medications']);
        if (!empty($newMedications)) {
            $changes['new_medications'] = $newMedications;
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