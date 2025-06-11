<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Patient;
use App\Models\PersonalInfo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting Patient Seeder - Creating diverse patient data for 2 years of bill analysis...');
        
        $faker = Faker::create();
        
        // Get patient role
        $patientRole = Role::where('code', 'patient')->first();
        
        if (!$patientRole) {
            $this->command->error('Patient role not found. Please run RoleSeeder first.');
            return;
        }
        
        // ✅ SCALED UP: More patients for 2 years of realistic bill data
        $patientCount = 1000; // Increased from 250 to support 2 years of bills
        $startDate = Carbon::create(2022, 1, 1); // Changed from 2023 to 2022
        $endDate = Carbon::create(2025, 12, 31);   // Extended to end of 2025
        
        // Distribution settings for more realistic data
        $genders = ['male', 'female', 'other'];
        $genderDistribution = [45, 45, 10]; // percentages
        
        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $bloodTypeDistribution = [35, 6, 8, 2, 3, 1, 38, 7]; // percentages
        
        $maritalStatuses = ['single', 'married', 'divorced', 'widowed'];
        $maritalDistribution = [30, 55, 10, 5]; // percentages
        
        // Geographic distribution (for address generation)
        $cities = [
            'Casablanca' => ['code' => 'CS', 'zip' => '20000'],
            'Rabat' => ['code' => 'RB', 'zip' => '10000'],
            'Marrakech' => ['code' => 'MR', 'zip' => '40000'],
            'Fes' => ['code' => 'FS', 'zip' => '30000'],
            'Tangier' => ['code' => 'TG', 'zip' => '90000'],
            'Agadir' => ['code' => 'AG', 'zip' => '80000'],
        ];
        
        // Track progress
        $progressBar = $this->command->getOutput()->createProgressBar($patientCount);
        $progressBar->start();
        
        // Generate patients
        for ($i = 0; $i < $patientCount; $i++) {
            // Generate registration date within our range
            $registrationDate = $faker->dateTimeBetween($startDate, $endDate);
            $registrationCarbon = Carbon::parse($registrationDate);
            
            // Determine birthdate (between 1 and 90 years old)
            $age = $faker->numberBetween(1, 90);
            $birthDate = $registrationCarbon->copy()->subYears($age)->subDays($faker->numberBetween(0, 364));
            
            // Select gender based on distribution
            $gender = $this->getWeightedRandom($genders, $genderDistribution);
            
            // First name based on gender
            $firstName = $gender === 'male' ? $faker->firstNameMale : ($gender === 'female' ? $faker->firstNameFemale : $faker->firstName);
            $lastName = $faker->lastName;
            
            // Generate user email
            $email = strtolower(str_replace(' ', '.', $firstName)) . '.' . 
                    strtolower(str_replace(' ', '', $lastName)) . 
                    $faker->numberBetween(1, 999) . '@' . $faker->safeEmailDomain;
            
            // Create user record
            $user = User::create([
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'password' => Hash::make('password'),
                'phone' => $faker->numerify('+212 5## ### ###'),
                'status' => 'active',
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);
            
            // Assign patient role
            $user->roles()->attach($patientRole->id);
            
            // Select random city
            $cityName = array_rand($cities);
            $city = $cities[$cityName];
            
            // Create address
            $address = $faker->streetAddress . ', ' . 
                      $cityName . ', ' . 
                      $city['zip'] . ', ' . 
                      'Morocco';
            
            // Create emergency contact (usually family member)
            $relationshipTypes = ['Spouse', 'Parent', 'Child', 'Sibling'];
            $emergencyRelation = $faker->randomElement($relationshipTypes);
            $emergencyName = $faker->name;
            $emergencyContact = $emergencyName . ' - ' . $faker->numerify('+212 6## ### ###') . ' (' . $emergencyRelation . ')';
            
            // ✅ FIXED: Create patient record with ONLY the columns that exist in the patients table
            $patient = Patient::create([
                'user_id' => $user->id,
                'registration_date' => $registrationDate,
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);
            
            // ✅ ALL detailed info goes to PersonalInfo table (which has the proper columns)
            PersonalInfo::create([
                'patient_id' => $patient->id,
                'name' => $firstName,
                'surname' => $lastName,
                'birthdate' => $birthDate->format('Y-m-d'),
                'gender' => $gender,
                'address' => $address,
                'emergency_contact' => $emergencyContact,
                'marital_status' => $this->getWeightedRandom($maritalStatuses, $maritalDistribution),
                'blood_type' => $this->getWeightedRandom($bloodTypes, $bloodTypeDistribution),
                'nationality' => $faker->randomElement(['Moroccan', 'Moroccan', 'Moroccan', 'Moroccan', 'French', 'Spanish', 'American', 'British']),
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->command->info("\nCreated {$patientCount} patients with proper user-patient linking");
        
        // ✅ Verification of proper linking
        $this->verifyPatientUserLinks();
    }
    
    /**
     * ✅ Verify that all patients have proper user links
     */
    private function verifyPatientUserLinks()
    {
        $patientsWithoutUsers = Patient::whereNull('user_id')->count();
        $usersWithoutPatients = User::whereHas('roles', function($q) {
            $q->where('code', 'patient');
        })->whereDoesntHave('patient')->count();
        
        if ($patientsWithoutUsers > 0) {
            $this->command->warn("Found {$patientsWithoutUsers} patients without user links!");
        }
        
        if ($usersWithoutPatients > 0) {
            $this->command->warn("Found {$usersWithoutPatients} patient users without patient records!");
        }
        
        if ($patientsWithoutUsers === 0 && $usersWithoutPatients === 0) {
            $this->command->info("✅ All patient-user relationships properly linked!");
        }
        
        // Additional verification
        $totalPatients = Patient::count();
        $totalPersonalInfos = PersonalInfo::count();
        
        $this->command->info("Created {$totalPatients} patient records and {$totalPersonalInfos} personal info records");
    }
    
    /**
     * Get a random element from an array based on weighted distribution
     * 
     * @param array $items Array of items
     * @param array $weights Array of weights (same order as items)
     * @return mixed Selected item
     */
    private function getWeightedRandom(array $items, array $weights)
    {
        $totalWeight = array_sum($weights);
        $randomNumber = mt_rand(1, $totalWeight);
        
        $weightSum = 0;
        foreach ($items as $index => $item) {
            $weightSum += $weights[$index];
            if ($randomNumber <= $weightSum) {
                return $item;
            }
        }
        
        return $items[0]; // Fallback
    }
}