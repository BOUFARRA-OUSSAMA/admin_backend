<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Patient;
use App\Models\User;
use App\Models\Role;
use App\Models\Doctor; // Add this line to import the Doctor model
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Faker\Factory as Faker;

class BillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting Bill Seeder - Generating comprehensive test data...');
        
        $faker = Faker::create();
        
        // Get or create doctors (at least 50)
        $doctorRole = Role::where('code', 'doctor')->first();
        $doctors = User::whereHas('roles', function($query) use ($doctorRole) {
            $query->where('roles.id', $doctorRole->id);
        })->get();
        
        if ($doctors->count() < 50) {
            $this->command->info('Creating additional doctors for testing...');

            
            // List of medical specialties
            $specialties = [
                'Cardiology', 'Dermatology', 'Endocrinology', 'Gastroenterology',
                'Neurology', 'Oncology', 'Pediatrics', 'Psychiatry', 'Radiology',
                'Urology', 'Family Medicine', 'Internal Medicine', 'General Surgery'
            ];
            
            for ($i = $doctors->count(); $i < 50; $i++) {
                $doctor = User::create([
                    'name' => 'Dr. ' . $faker->name,
                    'email' => 'doctor_' . Str::random(6) . '@example.com',
                    'password' => bcrypt('password'),
                    'status' => 'active',
                    'phone' => '+212 ' . $faker->numberBetween(6, 7) . $faker->numerify('## ### ###'),
                ]);
                $doctor->roles()->attach($doctorRole->id);
                
                // Create corresponding doctor specialty record
                Doctor::create([
                    'user_id' => $doctor->id,
                    'specialty' => $faker->randomElement($specialties),
                    'license_number' => 'LIC-' . strtoupper(Str::random(8)),
                    'experience_years' => rand(1, 30),
                    'consultation_fee' => rand(100, 1000),
                    'max_patient_appointments' => rand(5, 20),
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
                
                $doctors->push($doctor);
            }
        }
        
        // Get or create patients (at least 20)
        // Use existing patients from PatientSeeder
        $patients = Patient::all();
        
        if ($patients->isEmpty()) {
            $this->command->error('No patients found. Please run PatientSeeder first.');
            return;
        }
        
        // Ensure we have at least 20 patients for bill creation
        if ($patients->count() < 20) {
            $this->command->warning('Less than 20 patients found. Some bills may use the same patients repeatedly.');
        }
        
        $this->command->info('Using ' . $patients->count() . ' existing patients for bill generation');
        
        $patientRole = Role::where('code', 'patient')->first();
        
        // Get admin user for created_by field
        $adminUser = User::whereHas('roles', function($query) {
            $query->where('code', 'admin');
        })->first();
        
        if (!$adminUser) {
            $adminUser = User::first();
        }
        
        // Payment methods
        $paymentMethods = ['cash', 'credit_card', 'insurance', 'bank_transfer'];
        
        // Service codes with price ranges
        $serviceCodes = $this->getServiceCodes();
        
        // Define specific date range - all of 2022 to 2025
        $startDate = Carbon::create(2022, 1, 1, 0, 0, 0); // January 1, 2022
        $endDate = Carbon::create(2025, 12, 31, 23, 59, 59); // December 31, 2025
        
        $this->command->info('Generating bills from ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));
        
        // Calculate the approximate number of days in the range
        $dayCount = $endDate->diffInDays($startDate) + 1;
        
        // Track progress
        $progressBar = $this->command->getOutput()->createProgressBar($dayCount);
        $progressBar->start();
        
        // Track used bill numbers to avoid duplicates
        $usedBillNumbers = [];
        
        // Generate bills for EVERY DAY in the range
        $currentDate = clone $startDate;
        $billsCreated = 0;
        $chunkSize = 100; // Process in chunks for performance
        $billsBuffer = [];
        
        DB::disableQueryLog(); // Disable query logging for performance
        
        while ($currentDate->lte($endDate)) {
            // For each day, create 1-5 bills
            $dayBillCount = rand(1, 5);
            
            for ($i = 0; $i < $dayBillCount; $i++) {
                // Create a bill for this day with a random time
                $issueDateTime = (clone $currentDate)->setHour(rand(8, 17))->setMinute(rand(0, 59))->setSecond(rand(0, 59));
                
                // Generate a truly unique bill number using timestamp and UUID components
                // Format: BILL-YYYYMMDD-HHMMSS-XXXXX (where X is alphanumeric)
                $billNumber = 'BILL-' . $issueDateTime->format('Ymd-His') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 5);
                
                // Select a patient and doctor
                $patient = $patients->random();
                $doctor = $doctors->random();
                
                $bill = [
                    'patient_id' => $patient->id,
                    'doctor_user_id' => $doctor->id,
                    'bill_number' => $billNumber,
                    'issue_date' => $issueDateTime,
                    'amount' => 0, // Will be updated after adding items
                    'payment_method' => $faker->randomElement($paymentMethods),
                    'description' => $faker->optional(0.7)->sentence,
                    'created_by_user_id' => $adminUser->id,
                    'created_at' => $issueDateTime,
                    'updated_at' => $issueDateTime,
                    'pdf_path' => null,
                ];
                
                $billsBuffer[] = [
                    'data' => $bill,
                    'items_count' => rand(1, 6),
                    'date' => $issueDateTime
                ];
                
                // Process in chunks for better performance
                if (count($billsBuffer) >= $chunkSize) {
                    $this->processBillsChunk($billsBuffer, $serviceCodes, $faker, $adminUser);
                    $billsCreated += count($billsBuffer);
                    $billsBuffer = [];
                }
            }
            
            // Move to next day
            $currentDate->addDay();
            $progressBar->advance();
        }
        
        // Process any remaining bills
        if (count($billsBuffer) > 0) {
            $this->processBillsChunk($billsBuffer, $serviceCodes, $faker, $adminUser);
            $billsCreated += count($billsBuffer);
        }
        
        $progressBar->finish();
        $this->command->info("\nCreated {$billsCreated} bills with daily coverage over 2 years.");
        
        // Create special edge cases for testing
        $this->createEdgeCases($patients, $doctors, $adminUser, $serviceCodes, $faker);
    }
    
    /**
     * Process a chunk of bills for better performance
     */
    private function processBillsChunk(array $billsBuffer, array $serviceCodes, $faker, $adminUser)
    {
        // Use retry logic for Azure PostgreSQL connection resilience
        $maxRetries = 3;
        $attempt = 0;
        $success = false;
        
        while (!$success && $attempt < $maxRetries) {
            try {
                DB::beginTransaction();
                
                $billItems = [];
                $billAmounts = [];
                
                foreach ($billsBuffer as $billInfo) {
                    $bill = $billInfo['data'];
                    $itemsCount = $billInfo['items_count'];
                    $issueDate = $billInfo['date'];
                    
                    // Insert the bill
                    $billId = DB::table('bills')->insertGetId($bill);
                    
                    // Create items for this bill
                    $totalAmount = 0;
                    
                    // Make sure every service type is represented across bills
                    $serviceTypes = array_keys($serviceCodes);
                    
                    // Select random services for this bill
                    $selectedServices = array_rand($serviceCodes, min($itemsCount, count($serviceCodes)));
                    if (!is_array($selectedServices)) {
                        $selectedServices = [$selectedServices];
                    }
                    
                    foreach ($selectedServices as $serviceCode) {
                        $service = $serviceCodes[$serviceCode];
                        $price = $faker->numberBetween($service['price_range'][0], $service['price_range'][1]);
                        
                        $billItems[] = [
                            'bill_id' => $billId,
                            'service_type' => $serviceCode,
                            'description' => $service['name'] . ' - ' . $faker->sentence,
                            'price' => $price,
                            'total' => $price, // total is same as price (no quantity)
                            'created_at' => $issueDate,
                            'updated_at' => $issueDate,
                        ];
                        
                        $totalAmount += $price;
                    }
                    
                    $billAmounts[$billId] = $totalAmount;
                    
                    // Log activity
                    activity()
                        ->causedBy($adminUser)
                        ->performedOn(Bill::find($billId))
                        ->withProperties(['bill_number' => $bill['bill_number']])
                        ->log('Bill created');
                }
                
                // Insert all bill items in one batch - using chunk approach for Azure performance
                foreach (array_chunk($billItems, 50) as $billItemsChunk) {
                    DB::table('bill_items')->insert($billItemsChunk);
                }
                
                // Update bill amounts in bulk
                foreach ($billAmounts as $billId => $amount) {
                    DB::table('bills')
                        ->where('id', $billId)
                        ->update(['amount' => $amount]);
                }
                
                DB::commit();
                $success = true;
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                DB::rollBack();
                $attempt++;
                
                // On unique constraint violations, regenerate bill numbers and try again
                if ($attempt < $maxRetries) {
                    foreach ($billsBuffer as &$billInfo) {
                        $issueDateTime = $billInfo['date'];
                        $billInfo['data']['bill_number'] = 'BILL-' . $issueDateTime->format('Ymd-His') . '-' . 
                            substr(md5(uniqid(mt_rand() . $attempt, true)), 0, 5);
                    }
                    
                    // Add a small delay before retrying to avoid Azure throttling
                    usleep(500000); // 500ms
                } else {
                    throw $e; // Re-throw if max retries reached
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("Error creating bills: " . $e->getMessage());
                throw $e;
            }
        }
    }
    
    /**
     * Create edge case bills for testing all scenarios
     */
    private function createEdgeCases($patients, $doctors, $adminUser, $serviceCodes, $faker)
    {
        $this->command->info('Creating edge case bills for special scenarios...');
        
        $edgeCases = [
            // Very high-value bill
            [
                'name' => 'High-value surgery bill',
                'patient' => $patients->random(),
                'doctor' => $doctors->random(),
                'date' => Carbon::now()->subDays(rand(1, 30)),
                'payment_method' => 'insurance',
                'items' => [
                    ['service' => 'SURGERY', 'price' => 15000],
                    ['service' => 'LAB', 'price' => 800],
                    ['service' => 'SPECIALIST', 'price' => 500]
                ],
                'description' => 'Complex surgical procedure with specialized care'
            ],
            
            // Zero-amount bill
            [
                'name' => 'Zero-amount bill',
                'patient' => $patients->random(),
                'doctor' => $doctors->random(),
                'date' => Carbon::now()->subDays(rand(1, 10)),
                'payment_method' => 'cash',
                'items' => [
                    ['service' => 'CONSULT', 'price' => 0],
                    ['service' => 'CHECKUP', 'price' => 0]
                ],
                'description' => 'Complimentary services - no charge'
            ],
            
            // Bill with many items
            [
                'name' => 'Multi-item bill',
                'patient' => $patients->random(), 
                'doctor' => $doctors->random(),
                'date' => Carbon::now()->subDays(5),
                'payment_method' => 'credit_card',
                'serviceCount' => 12, // Will use multiple random services
                'description' => 'Comprehensive health evaluation with multiple services'
            ]
        ];
        
        foreach ($edgeCases as $case) {
            $this->command->info('Creating edge case: ' . $case['name']);
            
            $issueDate = $case['date'];
            // Use same format as regular bills but with EDGE prefix
            $billNumber = 'EDGE-' . $issueDate->format('Ymd-His') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 5);
            
            $bill = [
                'patient_id' => $case['patient']->id,
                'doctor_user_id' => $case['doctor']->id,
                'bill_number' => $billNumber,
                'issue_date' => $issueDate,
                'amount' => 0, // Will be updated after adding items
                'payment_method' => $case['payment_method'],
                'description' => $case['description'],
                'created_by_user_id' => $adminUser->id,
                'created_at' => $issueDate,
                'updated_at' => $issueDate,
                'pdf_path' => null,
            ];
            
            try {
                DB::beginTransaction();
                
                $billId = DB::table('bills')->insertGetId($bill);
                $totalAmount = 0;
                
                // Create specific items if defined
                if (isset($case['items'])) {
                    foreach ($case['items'] as $item) {
                        $serviceCode = $item['service'];
                        $price = $item['price'];
                        
                        DB::table('bill_items')->insert([
                            'bill_id' => $billId,
                            'service_type' => $serviceCode,
                            'description' => $serviceCodes[$serviceCode]['name'] . ' - ' . $faker->sentence,
                            'price' => $price,
                            'total' => $price,
                            'created_at' => $issueDate,
                            'updated_at' => $issueDate,
                        ]);
                        
                        $totalAmount += $price;
                    }
                } 
                // Create multiple random items if serviceCount is specified
                elseif (isset($case['serviceCount'])) {
                    $serviceKeys = array_keys($serviceCodes);
                    shuffle($serviceKeys);
                    $selectedServices = array_slice($serviceKeys, 0, min($case['serviceCount'], count($serviceKeys)));
                    
                    foreach ($selectedServices as $serviceCode) {
                        $service = $serviceCodes[$serviceCode];
                        $price = $faker->numberBetween($service['price_range'][0], $service['price_range'][1]);
                        
                        DB::table('bill_items')->insert([
                            'bill_id' => $billId,
                            'service_type' => $serviceCode,
                            'description' => $service['name'] . ' - ' . $faker->sentence,
                            'price' => $price,
                            'total' => $price,
                            'created_at' => $issueDate,
                            'updated_at' => $issueDate,
                        ]);
                        
                        $totalAmount += $price;
                    }
                }
                
                // Update the bill amount
                DB::table('bills')
                    ->where('id', $billId)
                    ->update(['amount' => $totalAmount]);
                    
                // Log activity
                activity()
                    ->causedBy($adminUser)
                    ->performedOn(Bill::find($billId))
                    ->withProperties(['bill_number' => $billNumber, 'edge_case' => $case['name']])
                    ->log('Edge case bill created');
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("Error creating edge case bill: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Get service codes with price ranges
     */
    private function getServiceCodes()
    {
        return [
            'CONSULT' => ['name' => 'Consultation', 'price_range' => [50, 200]],
            'CHECKUP' => ['name' => 'General Checkup', 'price_range' => [100, 300]],
            'XRAY' => ['name' => 'X-Ray Examination', 'price_range' => [150, 400]],
            'LAB' => ['name' => 'Laboratory Tests', 'price_range' => [80, 500]],
            'MEDS' => ['name' => 'Medication', 'price_range' => [20, 300]],
            'PHYSIO' => ['name' => 'Physiotherapy', 'price_range' => [70, 150]],
            'SURGERY' => ['name' => 'Surgical Procedure', 'price_range' => [500, 5000]],
            'DENTAL' => ['name' => 'Dental Work', 'price_range' => [100, 1000]],
            'THERAPY' => ['name' => 'Therapy Session', 'price_range' => [100, 250]],
            'EMERGENCY' => ['name' => 'Emergency Care', 'price_range' => [200, 1000]],
            'IMAGING' => ['name' => 'Advanced Imaging', 'price_range' => [300, 800]],
            'SPECIALIST' => ['name' => 'Specialist Consultation', 'price_range' => [150, 400]],
            'VACCINE' => ['name' => 'Vaccination', 'price_range' => [30, 150]],
            'REHAB' => ['name' => 'Rehabilitation', 'price_range' => [90, 250]],
        ];
    }
}