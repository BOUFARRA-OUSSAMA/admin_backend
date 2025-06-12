<?php

namespace App\Services\Medical;

use App\Models\Patient;
use App\Models\TimelineEvent;
use App\Models\VitalSign;
use App\Models\Medication;
use App\Models\PatientNote;
use App\Models\PatientFile;
use Illuminate\Database\Eloquent\Model;

class TimelineEventService
{
    /**
     * Create a timeline event for any medical activity.
     */    public function createTimelineEvent(
        Patient $patient,
        string $eventType,
        string $title,
        string $description,
        ?Model $relatedModel = null,
        string $importance = 'medium',
        bool $isVisibleToPatient = true,
        ?int $createdByUserId = null
    ): TimelineEvent {
        $data = [
            'patient_id' => $patient->id,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'event_date' => now(),
            'importance' => $importance,
            'is_visible_to_patient' => $isVisibleToPatient,
            'created_by_user_id' => $createdByUserId,
        ];

        if ($relatedModel) {
            $data['related_id'] = $relatedModel->id;
            $data['related_type'] = get_class($relatedModel);
        }

        return TimelineEvent::create($data);
    }

    /**
     * Auto-generate timeline event for vital signs recording.
     */
    public function createVitalSignsEvent(VitalSign $vitalSign): TimelineEvent
    {
        $patient = $vitalSign->patient;
        $recordedBy = $vitalSign->recordedBy;

        $description = $this->buildVitalSignsDescription($vitalSign);

        return $this->createTimelineEvent(
            $patient,
            'vital_signs',
            'Vital Signs Recorded',
            $description,
            $vitalSign,
            'low',
            true,            $recordedBy?->id
        );
    }

    /**
     * Auto-generate timeline event for prescription.
     */
    public function createPrescriptionEvent(Medication $medication): TimelineEvent
    {
        $patient = $medication->patient;
        $doctor = $medication->doctor;

        $description = "New prescription: {$medication->medication_name} ({$medication->dosage}) prescribed by Dr. {$doctor->name}";

        return $this->createTimelineEvent(
            $patient,
            'prescription',
            'New Medication Prescribed',
            $description,
            $medication,
            'medium',
            true,
            $doctor->id
        );
    }    /**
     * Auto-generate timeline event for medication discontinuation.
     */
    public function createMedicationDiscontinuedEvent(Medication $medication, $user, string $reason): TimelineEvent
    {
        $patient = $medication->patient;
        $doctorName = $user->name ?? 'Doctor';

        $description = "Medication discontinued: {$medication->medication_name} ({$medication->dosage}) by Dr. {$doctorName}. Reason: {$reason}";

        return $this->createTimelineEvent(
            $patient,
            'prescription', // Use 'prescription' instead of 'medication_discontinued'
            'Medication Discontinued',
            $description,
            $medication,
            'medium',
            true,
            $user->id
        );
    }

    /**
     * Auto-generate timeline event for lab result.
     */
    public function createLabResultEvent($labResult, $user): TimelineEvent
    {
        $patient = $labResult->patient;
        $doctorName = $user->name ?? 'Doctor';
        
        $testName = $labResult->structured_results['test_name'] ?? 'Lab Test';
        $description = "New lab result available: {$testName} - {$labResult->interpretation}";

        return $this->createTimelineEvent(
            $patient,
            'vital_signs', // Use existing event type
            'Lab Results Available',
            $description,
            $labResult,
            'medium',
            true,
            $user->id
        );
    }

    /**
     * Auto-generate timeline event for patient note.
     */
    public function createNoteEvent(PatientNote $note): TimelineEvent
    {
        $patient = $note->patient;
        $doctor = $note->doctor;

        $title = match($note->note_type) {
            'diagnosis' => 'New Diagnosis Recorded',
            'treatment' => 'Treatment Plan Updated',
            'follow_up' => 'Follow-up Note Added',
            default => 'Medical Note Added'
        };

        $description = "Dr. {$doctor->name} added a {$note->note_type} note: {$note->title}";

        return $this->createTimelineEvent(
            $patient,
            'note',
            $title,
            $description,
            $note,
            $note->note_type === 'diagnosis' ? 'high' : 'medium',
            !$note->is_private, // Visible only if note is not private
            $doctor->id
        );
    }    /**
     * Auto-generate timeline event for file upload.
     */
    public function createFileUploadEvent(PatientFile $file): TimelineEvent
    {
        $patient = $file->patient;
        $uploader = $file->uploadedBy;

        $description = "New {$file->category} file uploaded: {$file->original_filename}";
        if ($file->description) {
            $description .= " - {$file->description}";
        }

        return $this->createTimelineEvent(
            $patient,
            'file_upload',
            'Medical File Uploaded',
            $description,
            $file,
            'medium',
            $file->is_visible_to_patient,
            $uploader->id
        );
    }

    /**
     * Auto-generate timeline event for patient alert.
     */
    public function createAlertEvent($alert, $user, string $customDescription = null): TimelineEvent
    {
        $patient = $alert->patient;
        
        $title = match($alert->alert_type) {
            'allergy' => 'Allergy Alert',
            'medication' => 'Medication Alert',
            'condition' => 'Medical Condition Alert',
            default => 'Patient Alert'
        };

        $description = $customDescription ?? "New {$alert->alert_type} alert: {$alert->title} (Severity: {$alert->severity})";

        $importance = match($alert->severity) {
            'critical' => 'high',
            'high' => 'high',
            'medium' => 'medium',
            default => 'low'
        };

        return $this->createTimelineEvent(
            $patient,
            'alert', // Using 'alert' as event type
            $title,
            $description,
            $alert,
            $importance,
            true, // Alerts are usually visible to patients
            $user->id
        );
    }

    /**
     * Create manual timeline event.
     */public function createManualEvent(
        Patient $patient,
        string $title,
        string $description,
        string $importance = 'medium',
        ?int $createdByUserId = null
    ): TimelineEvent {
        return $this->createTimelineEvent(
            $patient,
            'manual',
            $title,
            $description,
            null,
            $importance,
            true,
            $createdByUserId
        );
    }

    /**
     * Get patient timeline events (filtered for patient view).
     */
    public function getPatientTimeline(Patient $patient, bool $isPatientView = false, int $limit = 20): array
    {
        $query = $patient->timelineEvents()->latest('event_date');

        if ($isPatientView) {
            $query->where('is_visible_to_patient', true);
        }

        $events = $query->limit($limit)->get();

        return $events->map(function ($event) use ($isPatientView) {
            return $event->toFrontendFormat($isPatientView);
        })->filter()->values()->toArray();
    }

    /**
     * Build description for vital signs event.
     */
    private function buildVitalSignsDescription(VitalSign $vitalSign): string
    {
        $parts = [];

        if ($vitalSign->blood_pressure_systolic && $vitalSign->blood_pressure_diastolic) {
            $parts[] = "BP: {$vitalSign->blood_pressure_systolic}/{$vitalSign->blood_pressure_diastolic} mmHg";
        }

        if ($vitalSign->pulse_rate) {
            $parts[] = "Pulse: {$vitalSign->pulse_rate} bpm";
        }

        if ($vitalSign->temperature) {
            $parts[] = "Temperature: {$vitalSign->temperature}{$vitalSign->temperature_unit}";
        }

        if ($vitalSign->oxygen_saturation) {
            $parts[] = "O2 Sat: {$vitalSign->oxygen_saturation}%";
        }

        return empty($parts) ? 'Vital signs recorded' : implode(', ', $parts);
    }
}
