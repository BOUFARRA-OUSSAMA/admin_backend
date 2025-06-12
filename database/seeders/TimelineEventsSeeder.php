<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\VitalSign;
use App\Models\Medication;
use App\Models\PatientNote;
use App\Models\PatientFile;
use App\Models\PatientAlert;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TimelineEventsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('📅 Seeding Timeline Events...');

        $faker = Faker::create();
        
        // Get all patients and doctors
        $patients = Patient::all();
        $doctors = User::whereHas('roles', function($query) {
            $query->where('code', 'doctor');
        })->get();

        if ($patients->isEmpty() || $doctors->isEmpty()) {
            $this->command->warn('⚠️  No patients or doctors found. Please run UserSeeder and PatientSeeder first.');
            return;
        }

        $eventTypes = ['appointment', 'prescription', 'vital_signs', 'note', 'file_upload', 'alert', 'manual'];
        $importanceLevels = ['low', 'medium', 'high'];
        $totalEvents = 0;

        foreach ($patients as $patient) {
            // Create 5-15 timeline events per patient
            $eventCount = $faker->numberBetween(5, 15);
            
            for ($i = 0; $i < $eventCount; $i++) {
                $eventDate = $faker->dateTimeBetween('-1 year', 'now');
                $eventType = $faker->randomElement($eventTypes);
                $doctor = $doctors->random();
                $importance = $faker->randomElement($importanceLevels);
                
                // Generate event data based on type
                $eventData = $this->generateEventData($faker, $eventType, $patient, $doctor);
                
                // Get related model data if available
                $relatedData = $this->getRelatedModel($patient, $eventType);
                
                TimelineEvent::create([
                    'patient_id' => $patient->id,
                    'event_type' => $eventType,
                    'title' => $eventData['title'],
                    'description' => $eventData['description'],
                    'event_date' => $eventDate,
                    'related_id' => $relatedData['id'],
                    'related_type' => $relatedData['type'],
                    'importance' => $importance,
                    'is_visible_to_patient' => $eventData['visible_to_patient'],
                    'created_by_user_id' => $doctor->id,
                    'created_at' => $eventDate,
                    'updated_at' => $eventDate,
                ]);
                
                $totalEvents++;
            }
        }        $this->command->info("✅ Created {$totalEvents} timeline events for {$patients->count()} patients");
    }    /**
     * Generate event data based on event type.
     */
    private function generateEventData($faker, string $eventType, Patient $patient, User $doctor): array
    {
        $patientName = $patient->personalInfo->first_name ?? 'Patient';
        $doctorName = $doctor->name;
        
        return match($eventType) {
            'appointment' => [
                'title' => $faker->randomElement([
                    'Appointment Completed',
                    'Medical Consultation',
                    'Follow-up Visit',
                    'Routine Check-up',
                ]),
                'description' => "Appointment with Dr. {$doctorName} completed. " . $faker->randomElement([
                    'Routine health assessment performed.',
                    'Follow-up on previous treatment plan.',
                    'General consultation and examination.',
                    'Discussed ongoing health concerns.',
                ]),
                'visible_to_patient' => true,
            ],
            
            'prescription' => [
                'title' => $faker->randomElement([
                    'New Medication Prescribed',
                    'Prescription Updated',
                    'Medication Started',
                    'Treatment Plan Modified',
                ]),
                'description' => "Dr. {$doctorName} prescribed " . $faker->randomElement([
                    'new medication for blood pressure management.',
                    'antibiotics for infection treatment.',
                    'pain medication for chronic condition.',
                    'updated dosage for existing medication.',
                ]),
                'visible_to_patient' => true,
            ],
            
            'vital_signs' => [
                'title' => 'Vital Signs Recorded',
                'description' => $faker->randomElement([
                    'Routine vital signs measurement completed. All parameters within normal range.',
                    'Blood pressure, heart rate, and temperature recorded during visit.',
                    'Vital signs monitoring as part of ongoing care plan.',
                    'Pre-appointment vital signs assessment completed.',
                ]),
                'visible_to_patient' => true,
            ],
            
            'note' => [
                'title' => $faker->randomElement([
                    'Medical Note Added',
                    'Doctor\'s Note',
                    'Clinical Assessment',
                    'Treatment Notes',
                ]),
                'description' => "Dr. {$doctorName} added " . $faker->randomElement([
                    'clinical notes regarding patient condition.',
                    'assessment notes following examination.',
                    'treatment plan documentation.',
                    'follow-up instructions and recommendations.',
                ]),
                'visible_to_patient' => $faker->boolean(70), // 70% visible to patient
            ],
            
            'file_upload' => [
                'title' => $faker->randomElement([
                    'Medical File Uploaded',
                    'Document Added',
                    'Lab Report Uploaded',
                    'Image File Added',
                ]),
                'description' => $faker->randomElement([
                    'X-ray images uploaded to patient record.',
                    'Lab report document added to medical files.',
                    'Insurance documentation uploaded.',
                    'Medical scan results added to patient file.',
                ]),
                'visible_to_patient' => true,
            ],
            
            'alert' => [
                'title' => $faker->randomElement([
                    'Patient Alert Created',
                    'Medical Alert Added',
                    'Important Notice',
                    'Health Alert',
                ]),
                'description' => $faker->randomElement([
                    'New allergy alert added to patient record.',
                    'Medication interaction warning created.',
                    'Important medical condition alert established.',
                    'Safety precaution alert added for patient care.',
                ]),
                'visible_to_patient' => true,
            ],
            
            'manual' => [
                'title' => $faker->randomElement([
                    'Manual Entry',
                    'Additional Note',
                    'Special Notation',
                    'Custom Event',
                ]),                'description' => "Dr. {$doctorName} manually added: " . $faker->randomElement([
                    'special instructions for patient care.',
                    'important observation not captured elsewhere.',
                    'additional context for treatment plan.',
                    'custom note regarding patient status.',
                ]),
                'visible_to_patient' => $faker->boolean(80), // 80% visible to patient
            ],
        };
    }

    /**
     * Get related model data for timeline event.
     */
    private function getRelatedModel(Patient $patient, string $eventType): array
    {
        return match($eventType) {
            'appointment' => [
                'id' => null, // Would link to actual appointment if available
                'type' => null,
            ],
            'prescription' => [
                'id' => $patient->medications()->inRandomOrder()->first()?->id,
                'type' => $patient->medications()->exists() ? 'App\\Models\\Medication' : null,
            ],
            'vital_signs' => [
                'id' => $patient->vitalSigns()->inRandomOrder()->first()?->id,
                'type' => $patient->vitalSigns()->exists() ? 'App\\Models\\VitalSign' : null,
            ],
            'note' => [
                'id' => $patient->patientNotes()->inRandomOrder()->first()?->id,
                'type' => $patient->patientNotes()->exists() ? 'App\\Models\\PatientNote' : null,
            ],
            'file_upload' => [
                'id' => $patient->patientFiles()->inRandomOrder()->first()?->id,
                'type' => $patient->patientFiles()->exists() ? 'App\\Models\\PatientFile' : null,
            ],
            'alert' => [
                'id' => $patient->patientAlerts()->inRandomOrder()->first()?->id,
                'type' => $patient->patientAlerts()->exists() ? 'App\\Models\\PatientAlert' : null,
            ],
            'manual' => [
                'id' => null,
                'type' => null,
            ],
        };
    }
}
