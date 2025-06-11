<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class DoctorSpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting Doctor Specialty Seeder - Adding specialties and phone numbers to all doctors...');
        
        $faker = Faker::create();
        
        // Get doctor role
        $doctorRole = Role::where('code', 'doctor')->first();
        
        if (!$doctorRole) {
            $this->command->error('Doctor role not found. Please run RoleSeeder first.');
            return;
        }
        
        // Get all users with doctor role
        $doctors = User::whereHas('roles', function($query) use ($doctorRole) {
            $query->where('roles.id', $doctorRole->id);
        })->get();
        
        $this->command->info("Found {$doctors->count()} doctor users to process");
        
        // List of medical specialties
        $specialties = [
            'Cardiology', 'Dermatology', 'Endocrinology', 'Gastroenterology',
            'Hematology', 'Infectious Disease', 'Nephrology', 'Neurology',
            'Obstetrics/Gynecology', 'Oncology', 'Ophthalmology', 'Orthopedics',
            'Otolaryngology', 'Pediatrics', 'Psychiatry', 'Pulmonology',
            'Radiology', 'Rheumatology', 'Urology', 'Family Medicine',
            'Internal Medicine', 'Emergency Medicine', 'General Surgery',
            'Plastic Surgery', 'Anesthesiology', 'Physical Medicine',
            'Allergy/Immunology', 'Dental Medicine'
        ];
        
        $progressBar = $this->command->getOutput()->createProgressBar($doctors->count());
        $progressBar->start();
        
        $specialtiesCreated = 0;
        $specialtiesUpdated = 0;
        $phonesUpdated = 0;
        
        foreach ($doctors as $doctor) {
            // First, ensure the doctor has a phone number
            if (empty($doctor->phone)) {
                $doctor->phone = '+212 ' . $faker->numberBetween(6, 7) . $faker->numerify('## ### ###');
                $doctor->save();
                $phonesUpdated++;
            }
            
            // Then check if the doctor already has a specialty record
            $doctorRecord = Doctor::where('user_id', $doctor->id)->first();
            
            if ($doctorRecord) {
                // Update existing record if specialty is null
                if (!$doctorRecord->specialty) {
                    $doctorRecord->update([
                        'specialty' => $specialties[array_rand($specialties)],
                    ]);
                    $specialtiesUpdated++;
                }
            } else {
                // Create new doctor record
                Doctor::create([
                    'user_id' => $doctor->id,
                    'specialty' => $specialties[array_rand($specialties)],
                    'license_number' => 'LIC-' . strtoupper(Str::random(8)),
                    'experience_years' => rand(1, 30),
                    'consultation_fee' => rand(100, 1000),
                    'max_patient_appointments' => rand(5, 25),
                    'is_available' => true,
                    'working_hours' => json_encode([
                        'monday' => ['09:00-12:00', '14:00-17:00'],
                        'tuesday' => ['09:00-12:00', '14:00-17:00'],
                        'wednesday' => ['09:00-12:00', '14:00-17:00'],
                        'thursday' => ['09:00-12:00', '14:00-17:00'],
                        'friday' => ['09:00-12:00', '14:00-17:00'],
                        'saturday' => ['09:00-12:00'],
                        'sunday' => []
                    ])
                ]);
                $specialtiesCreated++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->command->info("\nResults:");
        $this->command->info("- Created {$specialtiesCreated} new doctor specialty records");
        $this->command->info("- Updated {$specialtiesUpdated} existing records with specialties");
        $this->command->info("- Added {$phonesUpdated} phone numbers to doctor users");
    }
}