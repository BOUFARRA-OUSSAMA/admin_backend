<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientFile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class PatientFilesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeder.
     */    public function run(): void
    {
        $this->command->info('📁 Seeding Patient Files...');

        $faker = Faker::create();
        
        // Get all patients and users (for uploaders)
        $patients = Patient::all();
        $doctors = User::whereHas('roles', function($query) {
            $query->where('code', 'doctor');
        })->get();
        $allUsers = User::whereHas('roles', function($query) {
            $query->whereIn('code', ['doctor', 'patient', 'admin']);
        })->get();

        if ($patients->isEmpty() || $allUsers->isEmpty()) {
            $this->command->warn('⚠️  No patients or users found. Please run UserSeeder and PatientSeeder first.');
            return;
        }

        $fileTypes = ['image', 'document'];
        $categories = ['xray', 'scan', 'lab_report', 'insurance', 'other'];
        $totalFiles = 0;

        foreach ($patients as $patient) {
            // Create 0-5 files per patient (some patients may have no files)
            $fileCount = $faker->numberBetween(0, 5);
            
            for ($i = 0; $i < $fileCount; $i++) {
                $uploadDate = $faker->dateTimeBetween('-2 years', 'now');
                $fileType = $faker->randomElement($fileTypes);
                $category = $faker->randomElement($categories);
                
                // Choose uploader (patient themselves or a doctor)
                $uploader = $faker->boolean(60) 
                    ? $patient->user  // 60% uploaded by patient
                    : $doctors->random(); // 40% uploaded by doctor
                
                // Generate file data based on type and category
                $fileData = $this->generateFileData($faker, $fileType, $category);
                
                PatientFile::create([
                    'patient_id' => $patient->id,
                    'uploaded_by_user_id' => $uploader->id,
                    'file_type' => $fileType,
                    'category' => $category,
                    'original_filename' => $fileData['original_filename'],
                    'stored_filename' => $fileData['stored_filename'],
                    'file_path' => $fileData['file_path'],
                    'file_size' => $fileData['file_size'],
                    'mime_type' => $fileData['mime_type'],
                    'description' => $fileData['description'],
                    'is_visible_to_patient' => $faker->boolean(90), // 90% visible to patients
                    'uploaded_at' => $uploadDate,
                    'created_at' => $uploadDate,
                    'updated_at' => $uploadDate,
                ]);
                
                $totalFiles++;
            }
        }

        $this->command->info("✅ Created {$totalFiles} patient file records for {$patients->count()} patients");
        $this->command->warn('📝 Note: These are metadata records only. No actual files were created in storage.');
    }

    /**
     * Generate realistic file data based on type and category.
     */
    private function generateFileData($faker, string $fileType, string $category): array
    {
        // Generate file data based on category and type
        $fileData = $this->getFileDataByCategory($faker, $category, $fileType);
        
        // Generate unique stored filename
        $extension = $fileData['extension'];
        $storedFilename = Str::uuid() . '.' . $extension;
        $filePath = "patient-files/{$storedFilename}";
        
        return [
            'original_filename' => $fileData['filename'],
            'stored_filename' => $storedFilename,
            'file_path' => $filePath,
            'file_size' => $fileData['size'],
            'mime_type' => $fileData['mime_type'],
            'description' => $fileData['description'],
        ];
    }

    /**
     * Get file data templates based on category.
     */
    private function getFileDataByCategory($faker, string $category, string $fileType): array
    {
        return match($category) {
            'xray' => [
                'filename' => $faker->randomElement([
                    'chest_xray_' . $faker->date('Y_m_d') . '.jpg',
                    'knee_xray_lateral.png',
                    'spine_xray_ap.jpeg',
                    'hand_xray_pa.jpg',
                    'pelvis_xray.png',
                ]),
                'extension' => $faker->randomElement(['jpg', 'jpeg', 'png']),
                'mime_type' => $faker->randomElement(['image/jpeg', 'image/png']),
                'size' => $faker->numberBetween(500000, 8000000), // 500KB - 8MB
                'description' => $faker->randomElement([
                    'Chest X-ray for routine screening',
                    'Knee X-ray following injury',
                    'Spine X-ray for back pain evaluation',
                    'Hand X-ray post-fracture assessment',
                    'Pelvic X-ray for hip pain investigation',
                ]),
            ],
            
            'scan' => [
                'filename' => $faker->randomElement([
                    'mri_brain_' . $faker->date('Y_m_d') . '.jpg',
                    'ct_abdomen_contrast.png',
                    'ultrasound_gallbladder.jpeg',
                    'mri_knee_sagittal.jpg',
                    'ct_chest_hrct.png',
                ]),
                'extension' => $faker->randomElement(['jpg', 'jpeg', 'png']),
                'mime_type' => $faker->randomElement(['image/jpeg', 'image/png']),
                'size' => $faker->numberBetween(1000000, 10000000), // 1MB - 10MB
                'description' => $faker->randomElement([
                    'MRI brain scan for headache evaluation',
                    'CT abdomen with contrast for pain assessment',
                    'Ultrasound examination of gallbladder',
                    'MRI knee scan for sports injury',
                    'High-resolution CT chest scan',
                ]),
            ],
            
            'lab_report' => [
                'filename' => $faker->randomElement([
                    'blood_work_results_' . $faker->date('Y_m_d') . '.pdf',
                    'lipid_panel_report.pdf',
                    'thyroid_function_test.pdf',
                    'complete_metabolic_panel.pdf',
                    'urinalysis_results.pdf',
                ]),
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'size' => $faker->numberBetween(100000, 2000000), // 100KB - 2MB
                'description' => $faker->randomElement([
                    'Complete blood count and chemistry panel',
                    'Lipid profile for cardiovascular screening',
                    'Thyroid function assessment',
                    'Comprehensive metabolic panel results',
                    'Routine urinalysis findings',
                ]),
            ],
            
            'insurance' => [
                'filename' => $faker->randomElement([
                    'insurance_card_front.jpg',
                    'insurance_card_back.jpg',
                    'prior_authorization_form.pdf',
                    'insurance_claim_' . $faker->date('Y_m_d') . '.pdf',
                    'coverage_verification.pdf',
                ]),
                'extension' => $faker->randomElement(['pdf', 'jpg', 'jpeg', 'png']),
                'mime_type' => $faker->randomElement(['application/pdf', 'image/jpeg', 'image/png']),
                'size' => $faker->numberBetween(200000, 3000000), // 200KB - 3MB
                'description' => $faker->randomElement([
                    'Insurance card documentation',
                    'Prior authorization request form',
                    'Insurance claim documentation',
                    'Coverage verification letter',
                    'Benefits summary document',
                ]),
            ],
            
            'other' => [
                'filename' => $faker->randomElement([
                    'medical_history_form.pdf',
                    'patient_questionnaire.pdf',
                    'discharge_summary.pdf',
                    'referral_letter.pdf',
                    'vaccination_record.pdf',
                ]),
                'extension' => $fileType === 'image' ? $faker->randomElement(['jpg', 'jpeg', 'png']) : 'pdf',
                'mime_type' => $fileType === 'image' 
                    ? $faker->randomElement(['image/jpeg', 'image/png'])
                    : 'application/pdf',
                'size' => $faker->numberBetween(150000, 5000000), // 150KB - 5MB
                'description' => $faker->randomElement([
                    'Medical history intake form',
                    'Patient health questionnaire',
                    'Hospital discharge summary',
                    'Specialist referral letter',
                    'Vaccination history record',
                ]),
            ],
        };
    }
}
