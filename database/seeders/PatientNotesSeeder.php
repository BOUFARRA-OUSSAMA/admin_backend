<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientNote;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PatientNotesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeder.
     */    public function run(): void
    {
        $this->command->info('🗒️  Seeding Patient Notes...');

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

        $noteTypes = ['general', 'diagnosis', 'treatment', 'follow_up'];
        $totalNotes = 0;

        foreach ($patients as $patient) {
            // Create 2-6 notes per patient
            $noteCount = $faker->numberBetween(2, 6);
            
            for ($i = 0; $i < $noteCount; $i++) {
                $noteDate = $faker->dateTimeBetween('-1 year', 'now');
                $doctor = $doctors->random();
                $noteType = $faker->randomElement($noteTypes);
                
                // Generate content based on note type
                $content = $this->generateNoteContent($faker, $noteType, $patient);
                
                PatientNote::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'note_type' => $noteType,
                    'title' => $this->generateNoteTitle($faker, $noteType),
                    'content' => $content,
                    'is_private' => $faker->boolean(30), // 30% private notes
                    'created_at' => $noteDate,
                    'updated_at' => $noteDate,
                ]);
                
                $totalNotes++;
            }
        }

        $this->command->info("✅ Created {$totalNotes} patient notes for {$patients->count()} patients");
    }

    /**
     * Generate note title based on type.
     */
    private function generateNoteTitle($faker, string $noteType): string
    {
        return match($noteType) {
            'general' => $faker->randomElement([
                'General Health Assessment',
                'Routine Check-up Notes',
                'Patient Consultation',
                'Health Status Review',
                'General Examination',
            ]),
            'diagnosis' => $faker->randomElement([
                'Primary Diagnosis',
                'Diagnostic Assessment',
                'Clinical Findings',
                'Medical Diagnosis',
                'Condition Evaluation',
            ]),
            'treatment' => $faker->randomElement([
                'Treatment Plan',
                'Therapy Recommendations',
                'Medical Treatment',
                'Treatment Protocol',
                'Therapeutic Approach',
            ]),
            'follow_up' => $faker->randomElement([
                'Follow-up Visit',
                'Progress Review',
                'Post-Treatment Assessment',
                'Recovery Monitoring',
                'Ongoing Care Plan',
            ]),
        };
    }

    /**
     * Generate realistic note content based on type.
     */
    private function generateNoteContent($faker, string $noteType, Patient $patient): string
    {
        $patientName = $patient->personalInfo->first_name ?? 'Patient';
        
        return match($noteType) {
            'general' => $faker->randomElement([
                "Patient {$patientName} presents for routine health maintenance. Overall health appears good. Vital signs within normal limits. No acute concerns reported. Continue current medications and lifestyle. Schedule follow-up in 6 months.",
                
                "Comprehensive health assessment completed. Patient reports feeling well with no new symptoms. Physical examination unremarkable. Laboratory results reviewed and discussed. Preventive care recommendations provided.",
                
                "General consultation notes: Patient appears healthy and well-maintained. No significant changes since last visit. Discussed lifestyle modifications and preventive care measures. Patient educated on health maintenance strategies.",
            ]),
            
            'diagnosis' => $faker->randomElement([
                "Primary diagnosis: Hypertension, well-controlled. Secondary diagnosis: Type 2 diabetes mellitus, stable. Patient education provided regarding condition management and lifestyle modifications.",
                
                "Clinical assessment reveals mild osteoarthritis of the knees. X-rays show minimal joint space narrowing. Recommended conservative management with physical therapy and anti-inflammatory medications as needed.",
                
                "Diagnosis: Gastroesophageal reflux disease (GERD). Patient reports improvement with current medication regimen. Continue current treatment plan and dietary modifications. Monitor symptoms closely.",
            ]),
            
            'treatment' => $faker->randomElement([
                "Treatment plan updated: Continue current antihypertensive medication. Added metformin for glucose control. Patient counseled on medication compliance and potential side effects. Follow-up in 3 months.",
                
                "Therapeutic recommendations: Physical therapy for knee pain management. Prescribe low-impact exercise program. Consider joint supplements. Avoid high-impact activities. Monitor pain levels and functional improvement.",
                
                "Treatment protocol established: Proton pump inhibitor therapy continued. Dietary counseling provided. Recommend elevation of head of bed and avoiding trigger foods. Reassess in 8 weeks.",
            ]),
            
            'follow_up' => $faker->randomElement([
                "Follow-up visit: Patient doing well on current regimen. Blood pressure controlled within target range. No adverse effects reported. Continue current medications. Next appointment in 3 months.",
                
                "Progress review: Significant improvement in symptoms since last visit. Physical therapy showing positive results. Pain levels decreased from 7/10 to 4/10. Continue current treatment plan.",
                
                "Post-treatment assessment: Patient responding well to therapy. Laboratory values improved. No concerning symptoms reported. Discussed long-term management strategies and lifestyle modifications.",
            ]),
        };
    }
}
