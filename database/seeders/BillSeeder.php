<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Patient;
use App\Models\User;
use App\Models\Role;
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
        $faker = Faker::create();
        
        // Get doctor users
        $doctorRole = Role::where('code', 'doctor')->first();
        $doctors = User::whereHas('roles', function($query) use ($doctorRole) {
            $query->where('roles.id', $doctorRole->id);
        })->get();
        
        if ($doctors->isEmpty()) {
            // Create at least one doctor if none exists
            $doctor = User::create([
                'name' => 'Dr. ' . $faker->name,
                'email' => 'doctor_' . rand(1, 1000) . '@example.com',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
            $doctor->roles()->attach($doctorRole->id);
            $doctors = collect([$doctor]);
        }
        
        // Get all patients
        $patients = Patient::all();
        
        if ($patients->isEmpty()) {
            // Create some patients if none exist
            $patientRole = Role::where('code', 'patient')->first();
            
            for ($i = 0; $i < 5; $i++) {
                $patientUser = User::create([
                    'name' => $faker->name,
                    'email' => 'patient_' . rand(1, 1000) . '@example.com',
                    'password' => bcrypt('password'),
                    'status' => 'active',
                ]);
                
                $patientUser->roles()->attach($patientRole->id);
                
                $patient = Patient::create([
                    'user_id' => $patientUser->id,
                    'registration_date' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                ]);
                
                $patients->push($patient);
            }
        }
        
        // Get admin user for created_by field
        $adminUser = User::whereHas('roles', function($query) {
            $query->where('code', 'admin');
        })->first();
        
        if (!$adminUser) {
            $adminUser = User::first();
        }
        
        // Create bills with different payment methods
        $paymentMethods = ['cash', 'credit_card', 'insurance', 'bank_transfer'];
        
        // Generate bills for thorough testing
        $billCount = 50;
        $bills = [];
        
        // First, create the bill records
        for ($i = 0; $i < $billCount; $i++) {
            $patient = $patients->random();
            $doctor = $doctors->random();
            $issueDate = $faker->dateTimeBetween('-1 year', 'now');
            $dueDate = (clone $issueDate)->modify('+30 days');
            
            // Generate bill number
            $billNumber = 'BILL-' . date('Ym', $issueDate->getTimestamp()) . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $bill = [
                'patient_id' => $patient->id,
                'doctor_user_id' => $doctor->id,
                'bill_number' => $billNumber,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'amount' => 0, // Will be updated after adding items
                'payment_method' => $faker->randomElement($paymentMethods),
                'description' => $faker->optional(0.7)->sentence,
                'created_by_user_id' => $adminUser->id,
                'created_at' => $issueDate,
                'updated_at' => $issueDate,
                'pdf_path' => null,
            ];
            
            $billId = DB::table('bills')->insertGetId($bill);
            $bills[] = ['id' => $billId, 'date' => $issueDate];
        }
        
        // Now seed the bill_items table with multiple items per bill
        $serviceCodes = [
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
        ];
        
        foreach ($bills as $bill) {
            // Add 1-6 items to each bill
            $itemCount = rand(1, 6);
            $totalAmount = 0;
            
            // Select random services without repeating
            $selectedServices = array_rand($serviceCodes, min($itemCount, count($serviceCodes)));
            if (!is_array($selectedServices)) {
                $selectedServices = [$selectedServices];
            }
            
            foreach ($selectedServices as $serviceCode) {
                $service = $serviceCodes[$serviceCode];
                $price = $faker->numberBetween($service['price_range'][0], $service['price_range'][1]);
                $quantity = $faker->numberBetween(1, 3);
                $total = $price * $quantity;
                
                DB::table('bill_items')->insert([
                    'bill_id' => $bill['id'],
                    'service_type' => $serviceCode,
                    'description' => $service['name'] . ' - ' . $faker->sentence,
                    'price' => $price,
                    'quantity' => $quantity,
                    'total' => $total,
                    'created_at' => $bill['date'],
                    'updated_at' => $bill['date'],
                ]);
                
                $totalAmount += $total;
            }
            
            // Update the bill amount
            DB::table('bills')
                ->where('id', $bill['id'])
                ->update(['amount' => $totalAmount]);
            
            // Simulate activity logging
            activity()
                ->causedBy($adminUser)
                ->performedOn(Bill::find($bill['id']))
                ->withProperties(['bill_number' => DB::table('bills')->where('id', $bill['id'])->value('bill_number')])
                ->log('Bill created');
        }
        
        $this->command->info('Created ' . count($bills) . ' bills with multiple items each.');
    }
}