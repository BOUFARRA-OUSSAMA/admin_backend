<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\User;
use App\Models\VitalSign;
use App\Models\Medication;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\PatientNote;
use App\Models\PatientAlert;
use App\Models\TimelineEvent;
use App\Models\PatientFile;
use App\Models\MedicalHistory;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Storage;

class MedicalDataSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->command->info('🏥 Creating comprehensive medical data for all patients...');
        
        // Get all patients
        $patients = Patient::with('user')->get();
        
        if ($patients->isEmpty()) {
            $this->command->error('No patients found. Please run PatientSeeder first.');
            return;
        }

        // Get doctors for created_by relationships
        $doctors = User::whereHas('roles', function($query) {
            $query->where('code', 'doctor');
        })->get();

        if ($doctors->isEmpty()) {
            $this->command->error('No doctors found. Please run doctor seeders first.');
            return;
        }

        $this->command->info("Found {$patients->count()} patients and {$doctors->count()} doctors");

        // Create progress bar
        $progressBar = $this->command->getOutput()->createProgressBar($patients->count());
        $progressBar->start();

        foreach ($patients as $patient) {
            $this->createPatientMedicalData($patient, $doctors);
            $progressBar->advance();
        }

        $progressBar->finish();

        // Show summary
        $this->showDataSummary();
    }

    /**
     * Create comprehensive medical data for a single patient
     */
    private function createPatientMedicalData(Patient $patient, $doctors)
    {
        // Create vital signs (multiple entries over time)
        $this->createVitalSigns($patient, $doctors);
        
        // Create medications (current and past)
        $this->createMedications($patient, $doctors);
        
        // Create lab tests and results
        $this->createLabTestsAndResults($patient, $doctors);
        
        // Create medical history entries
        $this->createMedicalHistory($patient, $doctors);
        
        // Create patient notes (doctor notes)
        $this->createPatientNotes($patient, $doctors);
        
        // Create patient alerts
        $this->createPatientAlerts($patient, $doctors);
        
        // Create sample patient files (metadata only)
        $this->createPatientFiles($patient, $doctors);
    }

    /**
     * Create vital signs for a patient over time
     */
    private function createVitalSigns(Patient $patient, $doctors)
    {
        // Create 3-8 vital sign entries over the past year
        $entryCount = rand(3, 8);
        
        for ($i = 0; $i < $entryCount; $i++) {
            $recordedDate = now()->subDays(rand(7, 365));
            $doctor = $doctors->random();

            VitalSign::create([
                'patient_id' => $patient->id,
                'recorded_by_user_id' => $doctor->id,
                'blood_pressure_systolic' => rand(110, 160),
                'blood_pressure_diastolic' => rand(70, 100),
                'pulse_rate' => rand(60, 100),
                'temperature' => round($this->faker->randomFloat(1, 36.0, 38.5), 1),
                'temperature_unit' => '°C',
                'respiratory_rate' => rand(12, 20),
                'oxygen_saturation' => rand(95, 100),
                'weight' => $this->faker->randomFloat(1, 50.0, 120.0),
                'weight_unit' => 'kg',
                'height' => rand(150, 190),
                'height_unit' => 'cm',
                'notes' => $this->faker->optional(0.3)->sentence(),
                'recorded_at' => $recordedDate,
                'created_at' => $recordedDate,
                'updated_at' => $recordedDate,
            ]);
        }
    }

    /**
     * Create medications for a patient
     */
    private function createMedications(Patient $patient, $doctors)
    {
        $medications = [
            ['name' => 'Lisinopril', 'dosage' => '10mg', 'frequency' => 'Once daily'],
            ['name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'Twice daily'],
            ['name' => 'Atorvastatin', 'dosage' => '20mg', 'frequency' => 'Once daily'],
            ['name' => 'Amlodipine', 'dosage' => '5mg', 'frequency' => 'Once daily'],
            ['name' => 'Omeprazole', 'dosage' => '20mg', 'frequency' => 'Once daily'],
            ['name' => 'Aspirin', 'dosage' => '81mg', 'frequency' => 'Once daily'],
            ['name' => 'Albuterol', 'dosage' => '2 puffs', 'frequency' => 'As needed'],
            ['name' => 'Hydrochlorothiazide', 'dosage' => '25mg', 'frequency' => 'Once daily'],
            ['name' => 'Levothyroxine', 'dosage' => '50mcg', 'frequency' => 'Once daily'],
            ['name' => 'Gabapentin', 'dosage' => '300mg', 'frequency' => 'Three times daily'],
        ];

        // Create 1-4 medications per patient
        $medCount = rand(1, 4);
        $selectedMeds = $this->faker->randomElements($medications, $medCount);        foreach ($selectedMeds as $med) {
            $prescribedDate = now()->subDays(rand(30, 365));
            $doctor = $doctors->random();
            $isActive = $this->faker->boolean(80); // 80% chance of being active

            Medication::create([
                'patient_id' => $patient->id,
                'doctor_user_id' => $doctor->id,  // Use doctor_user_id instead of prescribed_by_user_id
                'medication_name' => $med['name'],
                'dosage' => $med['dosage'],
                'frequency' => $med['frequency'],
                'duration' => $this->faker->randomElement(['7 days', '30 days', '90 days', 'ongoing']),
                'start_date' => $prescribedDate,
                'end_date' => $isActive ? null : $prescribedDate->copy()->addDays(rand(30, 180)),
                'instructions' => "Take {$med['dosage']} {$med['frequency']}. " . $this->faker->sentence(),
                'refills_allowed' => rand(0, 5),
                'status' => $isActive ? 'active' : 'completed',
                'created_at' => $prescribedDate,
                'updated_at' => $prescribedDate,
            ]);
        }
    }

    /**
     * Create lab tests and results for a patient
     */
    private function createLabTestsAndResults(Patient $patient, $doctors)
    {
        $labTests = [
            ['name' => 'Complete Blood Count (CBC)', 'category' => 'hematology'],
            ['name' => 'Comprehensive Metabolic Panel', 'category' => 'chemistry'],
            ['name' => 'Lipid Panel', 'category' => 'chemistry'],
            ['name' => 'Thyroid Function Tests', 'category' => 'endocrinology'],
            ['name' => 'Hemoglobin A1C', 'category' => 'diabetes'],
            ['name' => 'Vitamin D', 'category' => 'vitamins'],
            ['name' => 'PSA (Prostate-Specific Antigen)', 'category' => 'oncology'],
            ['name' => 'Urinalysis', 'category' => 'urology'],
        ];

        // Create 2-5 lab tests per patient
        $testCount = rand(2, 5);
        $selectedTests = $this->faker->randomElements($labTests, $testCount);        foreach ($selectedTests as $test) {
            $orderedDate = now()->subDays(rand(30, 365));
            $resultDate = $orderedDate->copy()->addDays(rand(1, 7));
            $doctor = $doctors->random();

            // Create lab test
            $labTest = LabTest::create([
                'patient_id' => $patient->id,
                'requested_by_user_id' => $doctor->id,
                'test_name' => $test['name'],
                'test_code' => strtoupper(substr($test['name'], 0, 3)) . rand(100, 999),
                'urgency' => $this->faker->randomElement(['routine', 'urgent', 'stat']),
                'requested_date' => $orderedDate,
                'scheduled_date' => $orderedDate->copy()->addDays(1),
                'lab_name' => $this->faker->randomElement(['City Lab', 'MedTest Labs', 'Health Diagnostics']),
                'status' => 'completed',
                'created_at' => $orderedDate,
                'updated_at' => $resultDate,
            ]);

            // Create lab result
            $this->createLabResult($labTest, $resultDate, $doctor);
        }
    }    /**
     * Create a lab result for a lab test
     */
    private function createLabResult(LabTest $labTest, Carbon $resultDate, User $doctor)
    {
        $resultData = $this->generateLabResultData($labTest->test_name);

        LabResult::create([
            'lab_test_id' => $labTest->id,
            'medical_record_id' => null, // We'll link this later if needed
            'result_date' => $resultDate,
            'performed_by_lab_name' => $this->faker->randomElement(['City Lab', 'MedTest Labs', 'Health Diagnostics', 'Quest Diagnostics']),
            'result_document_path' => null, // Optional file path
            'structured_results' => json_encode([
                'test_name' => $labTest->test_name,
                'results' => $resultData['results'],
                'overall_status' => $resultData['status']
            ]),
            'interpretation' => $resultData['interpretation'],
            'reviewed_by_user_id' => $doctor->id,
            'status' => $this->faker->randomElement(['pending_review', 'reviewed', 'requires_action']),
            'created_at' => $resultDate,
            'updated_at' => $resultDate,
        ]);
    }    /**
     * Generate realistic lab result data based on test name
     */
    private function generateLabResultData(string $testName): array
    {
        switch ($testName) {
            case 'Complete Blood Count (CBC)':
                $wbc = rand(4, 11) . '.0';
                $rbc = $this->faker->randomFloat(2, 4.0, 5.5);
                $hgb = $this->faker->randomFloat(1, 12.0, 16.0);
                return [
                    'results' => [
                        ['component' => 'WBC', 'value' => $wbc, 'range' => '4.5-11.0', 'unit' => '10³/μL'],
                        ['component' => 'RBC', 'value' => $rbc, 'range' => '4.2-5.4', 'unit' => '10⁶/μL'],
                        ['component' => 'Hemoglobin', 'value' => $hgb, 'range' => '12.0-15.5', 'unit' => 'g/dL']
                    ],
                    'status' => $this->faker->randomElement(['normal', 'abnormal']),
                    'interpretation' => 'Complete blood count shows ' . $this->faker->randomElement(['normal values', 'mild anemia', 'elevated white cells'])
                ];
            
            case 'Comprehensive Metabolic Panel':
                $glucose = rand(70, 140);
                $creatinine = $this->faker->randomFloat(2, 0.6, 1.3);
                return [
                    'results' => [
                        ['component' => 'Glucose', 'value' => $glucose, 'range' => '70-100', 'unit' => 'mg/dL'],
                        ['component' => 'Creatinine', 'value' => $creatinine, 'range' => '0.7-1.3', 'unit' => 'mg/dL'],
                        ['component' => 'Sodium', 'value' => rand(136, 145), 'range' => '136-145', 'unit' => 'mEq/L']
                    ],
                    'status' => $glucose >= 70 && $glucose <= 100 ? 'normal' : 'abnormal',
                    'interpretation' => 'Metabolic panel shows ' . $this->faker->randomElement(['normal kidney function', 'borderline glucose levels', 'normal electrolytes'])
                ];
            
            case 'Lipid Panel':
                $cholesterol = rand(150, 300);
                $hdl = rand(30, 80);
                $ldl = rand(70, 190);
                return [
                    'results' => [
                        ['component' => 'Total Cholesterol', 'value' => $cholesterol, 'range' => '<200', 'unit' => 'mg/dL'],
                        ['component' => 'HDL', 'value' => $hdl, 'range' => '>40', 'unit' => 'mg/dL'],
                        ['component' => 'LDL', 'value' => $ldl, 'range' => '<100', 'unit' => 'mg/dL']
                    ],
                    'status' => $cholesterol < 200 ? 'normal' : 'abnormal',
                    'interpretation' => 'Lipid levels show ' . $this->faker->randomElement(['good cholesterol control', 'elevated cholesterol', 'borderline lipid levels'])
                ];
            
            case 'Thyroid Function Tests':
                $tsh = $this->faker->randomFloat(2, 0.5, 5.0);
                return [
                    'results' => [
                        ['component' => 'TSH', 'value' => $tsh, 'range' => '0.4-4.0', 'unit' => 'mIU/L'],
                        ['component' => 'Free T4', 'value' => $this->faker->randomFloat(2, 0.8, 1.8), 'range' => '0.9-1.7', 'unit' => 'ng/dL']
                    ],
                    'status' => $this->faker->randomElement(['normal', 'abnormal']),
                    'interpretation' => 'Thyroid function appears ' . $this->faker->randomElement(['normal', 'slightly elevated', 'within normal limits'])
                ];
            
            case 'Hemoglobin A1C':
                $a1c = $this->faker->randomFloat(1, 4.5, 9.0);
                return [
                    'results' => [
                        ['component' => 'Hemoglobin A1C', 'value' => $a1c, 'range' => '<7.0', 'unit' => '%']
                    ],
                    'status' => $a1c < 7.0 ? 'normal' : 'abnormal',
                    'interpretation' => $a1c < 7.0 ? 'Good diabetes control' : 'Diabetes control needs improvement'
                ];
            
            default:
                $value = $this->faker->randomFloat(2, 1.0, 100.0);
                return [
                    'results' => [
                        ['component' => 'Test Result', 'value' => $value, 'range' => '5.0-50.0', 'unit' => 'unit']
                    ],
                    'status' => $this->faker->randomElement(['normal', 'abnormal']),
                    'interpretation' => 'Test results are ' . $this->faker->randomElement(['within normal limits', 'slightly elevated', 'borderline'])
                ];
        }
    }

    /**
     * Create medical history entries for a patient
     */
    private function createMedicalHistory(Patient $patient, $doctors)
    {
        $conditions = [
            'Hypertension', 'Diabetes Mellitus Type 2', 'Hyperlipidemia', 
            'Asthma', 'GERD', 'Arthritis', 'Anxiety', 'Depression',
            'Thyroid Disease', 'Sleep Apnea'
        ];

        $allergies = [
            'Penicillin', 'Sulfa drugs', 'Latex', 'Peanuts', 'Shellfish',
            'Iodine', 'Aspirin', 'NSAIDs'
        ];

        $surgeries = [
            'Appendectomy', 'Cholecystectomy', 'Knee replacement',
            'Cataract surgery', 'Hernia repair', 'Tonsillectomy'
        ];

        // Create 0-2 medical history entries per patient
        $historyCount = rand(0, 2);
          for ($i = 0; $i < $historyCount; $i++) {
            $entryDate = now()->subDays(rand(90, 730));
            $doctor = $doctors->random();

            MedicalHistory::create([
                'patient_id' => $patient->id,
                'current_medical_conditions' => json_encode($this->faker->randomElements($conditions, rand(0, 3))),
                'past_surgeries' => json_encode($this->faker->randomElements($surgeries, rand(0, 2))),
                'chronic_diseases' => json_encode($this->faker->randomElements($conditions, rand(0, 2))),
                'current_medications' => json_encode($this->faker->randomElements(['Lisinopril', 'Metformin'], rand(0, 2))),
                'allergies' => json_encode($this->faker->randomElements($allergies, rand(0, 2))),
                'last_updated' => $entryDate,
                'updated_by_user_id' => $doctor->id,
                'created_at' => $entryDate,
                'updated_at' => $entryDate,
            ]);
        }
    }

    /**
     * Create patient notes (doctor notes)
     */
    private function createPatientNotes(Patient $patient, $doctors)
    {
        $noteTypes = ['general', 'diagnosis', 'treatment', 'follow_up'];
        
        // Create 2-6 notes per patient
        $noteCount = rand(2, 6);
        
        for ($i = 0; $i < $noteCount; $i++) {
            $noteDate = now()->subDays(rand(7, 365));
            $doctor = $doctors->random();            PatientNote::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,  // Use doctor_id instead of created_by_user_id
                'note_type' => $this->faker->randomElement($noteTypes),  // Use note_type instead of type
                'title' => $this->faker->sentence(3),
                'content' => $this->faker->paragraph(rand(2, 5)),
                'is_private' => $this->faker->boolean(70), // 70% private (doctor only)
                'created_at' => $noteDate,
                'updated_at' => $noteDate,
            ]);
        }
    }    /**
     * Create patient alerts
     */
    private function createPatientAlerts(Patient $patient, $doctors)
    {
        $alertTypes = ['allergy', 'medication', 'condition', 'warning'];  // Match enum values
        $severityLevels = ['low', 'medium', 'high', 'critical'];
        
        // Create 0-3 alerts per patient (not all patients have alerts)
        $alertCount = rand(0, 3);
        
        for ($i = 0; $i < $alertCount; $i++) {
            $alertDate = now()->subDays(rand(30, 365));

            PatientAlert::create([
                'patient_id' => $patient->id,
                'alert_type' => $this->faker->randomElement($alertTypes),  // Use alert_type
                'severity' => $this->faker->randomElement($severityLevels),
                'title' => $this->faker->sentence(2),
                'description' => $this->faker->sentence(),  // Use description instead of message
                'is_active' => $this->faker->boolean(80), // 80% active
                'created_at' => $alertDate,
                'updated_at' => $alertDate,
            ]);
        }
    }

    /**
     * Create sample patient files (metadata only, no actual files)
     */
    private function createPatientFiles(Patient $patient, $doctors)
    {
        $fileTypes = ['image', 'document'];
        $categories = ['xray', 'scan', 'lab_report', 'insurance', 'other'];
        
        // Create 0-4 files per patient
        $fileCount = rand(0, 4);
        
        for ($i = 0; $i < $fileCount; $i++) {
            $uploadDate = now()->subDays(rand(7, 365));
            $uploader = $this->faker->randomElement([$patient->user, $doctors->random()]);
            $fileType = $this->faker->randomElement($fileTypes);
            
            $fileName = $this->generateSampleFileName($fileType);
            $storedFileName = uniqid() . '_' . $fileName;

            PatientFile::create([
                'patient_id' => $patient->id,
                'uploaded_by_user_id' => $uploader->id,
                'file_type' => $fileType,
                'category' => $this->faker->randomElement($categories),
                'original_filename' => $fileName,
                'stored_filename' => $storedFileName,
                'file_path' => "patient_files/{$patient->id}/{$storedFileName}",
                'file_size' => rand(50000, 5000000), // 50KB to 5MB
                'mime_type' => $fileType === 'image' ? 'image/jpeg' : 'application/pdf',
                'description' => $this->faker->optional(0.6)->sentence(),
                'uploaded_at' => $uploadDate,
                'created_at' => $uploadDate,
                'updated_at' => $uploadDate,
            ]);
        }
    }

    /**
     * Generate a realistic filename based on file type
     */
    private function generateSampleFileName(string $fileType): string
    {
        if ($fileType === 'image') {
            $types = ['chest_xray', 'knee_mri', 'blood_test', 'ultrasound', 'ct_scan'];
            return $this->faker->randomElement($types) . '_' . now()->format('Y_m_d') . '.jpg';
        } else {
            $types = ['lab_report', 'insurance_form', 'medical_summary', 'prescription', 'referral'];
            return $this->faker->randomElement($types) . '_' . now()->format('Y_m_d') . '.pdf';
        }
    }

    /**
     * Show summary of created data
     */
    private function showDataSummary()
    {
        $this->command->info("\n✅ Medical data seeding completed!");
        $this->command->info("\n📊 **DATA SUMMARY:**");
        $this->command->info("• Vital Signs: " . VitalSign::count());
        $this->command->info("• Medications: " . Medication::count());
        $this->command->info("• Lab Tests: " . LabTest::count());
        $this->command->info("• Lab Results: " . LabResult::count());
        $this->command->info("• Medical Histories: " . MedicalHistory::count());
        $this->command->info("• Patient Notes: " . PatientNote::count());
        $this->command->info("• Patient Alerts: " . PatientAlert::count());
        $this->command->info("• Patient Files: " . PatientFile::count());
        $this->command->info("• Timeline Events: " . TimelineEvent::count());
        
        $this->command->info("\n🎯 **NEXT STEPS:**");
        $this->command->info("1. Test API endpoints with GET requests");
        $this->command->info("2. Verify data relationships and transformations");
        $this->command->info("3. Test file upload/download functionality");
        $this->command->info("4. Connect frontend to real APIs");
    }
}
