<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PatientAlertsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🚨 Seeding Patient Alerts...');

        $faker = Faker::create();
        
        // Get all patients
        $patients = Patient::all();

        if ($patients->isEmpty()) {
            $this->command->warn('⚠️  No patients found. Please run PatientSeeder first.');
            return;
        }

        $alertTypes = ['allergy', 'medication', 'condition', 'warning'];
        $severityLevels = ['low', 'medium', 'high', 'critical'];
        $totalAlerts = 0;

        foreach ($patients as $patient) {
            // Create 0-4 alerts per patient (some patients may have no alerts)
            $alertCount = $faker->numberBetween(0, 4);
            
            for ($i = 0; $i < $alertCount; $i++) {
                $alertDate = $faker->dateTimeBetween('-2 years', 'now');
                $alertType = $faker->randomElement($alertTypes);
                $severity = $faker->randomElement($severityLevels);
                
                // Generate realistic alert data
                $alertData = $this->generateAlertData($faker, $alertType, $severity);
                
                PatientAlert::create([
                    'patient_id' => $patient->id,
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'title' => $alertData['title'],
                    'description' => $alertData['description'],
                    'is_active' => $faker->boolean(85), // 85% of alerts are active
                    'created_at' => $alertDate,
                    'updated_at' => $alertDate,
                ]);
                
                $totalAlerts++;
            }
        }

        $this->command->info("✅ Created {$totalAlerts} patient alerts for {$patients->count()} patients");
    }

    /**
     * Generate realistic alert data based on type and severity.
     */
    private function generateAlertData($faker, string $alertType, string $severity): array
    {
        return match($alertType) {
            'allergy' => $this->generateAllergyAlert($faker, $severity),
            'medication' => $this->generateMedicationAlert($faker, $severity),
            'condition' => $this->generateConditionAlert($faker, $severity),
            'warning' => $this->generateWarningAlert($faker, $severity),
        };
    }

    /**
     * Generate allergy alert data.
     */
    private function generateAllergyAlert($faker, string $severity): array
    {
        $allergies = [
            'low' => ['Seasonal pollen', 'Dust mites', 'Pet dander', 'Certain foods'],
            'medium' => ['Latex', 'Shellfish', 'Tree nuts', 'Dairy products'],
            'high' => ['Penicillin', 'Sulfa drugs', 'Bee stings', 'Peanuts'],
            'critical' => ['Multiple drug allergies', 'Severe anaphylaxis risk', 'Life-threatening food allergies']
        ];

        $allergen = $faker->randomElement($allergies[$severity]);
        
        return [
            'title' => "Allergy: {$allergen}",
            'description' => match($severity) {
                'low' => "Mild allergic reaction to {$allergen}. May cause sneezing, runny nose, or mild skin irritation.",
                'medium' => "Moderate allergy to {$allergen}. Can cause significant discomfort including hives or swelling.",
                'high' => "Severe allergy to {$allergen}. Risk of serious allergic reaction. Strict avoidance required.",
                'critical' => "Life-threatening allergy to {$allergen}. Risk of anaphylaxis. Patient must carry EpiPen at all times."
            }
        ];
    }

    /**
     * Generate medication alert data.
     */
    private function generateMedicationAlert($faker, string $severity): array
    {
        $medications = [
            'low' => ['Ibuprofen', 'Aspirin', 'Acetaminophen'],
            'medium' => ['ACE inhibitors', 'Beta blockers', 'Statins'],
            'high' => ['Insulin', 'Chemotherapy drugs', 'Immunosuppressants'],
            'critical' => ['Multiple drug interactions', 'High-risk medications']
        ];

        $medication = $faker->randomElement($medications[$severity]);
        
        return [
            'title' => "Medication Alert: {$medication}",
            'description' => match($severity) {
                'low' => "Caution with {$medication}. Monitor for mild side effects.",
                'medium' => "Important medication consideration for {$medication}. Requires regular monitoring.",
                'high' => "High-priority medication alert for {$medication}. Requires close monitoring for serious side effects.",
                'critical' => "Critical medication alert: {$medication}. Extremely high risk for serious adverse effects."
            }
        ];
    }

    /**
     * Generate medical condition alert data.
     */
    private function generateConditionAlert($faker, string $severity): array
    {
        $conditions = [
            'low' => ['Mild hypertension', 'Pre-diabetes', 'Minor heart murmur'],
            'medium' => ['Type 2 diabetes', 'Asthma', 'Moderate kidney disease'],
            'high' => ['Heart disease', 'COPD', 'Chronic kidney disease'],
            'critical' => ['End-stage organ disease', 'Multiple organ dysfunction', 'Terminal illness']
        ];

        $condition = $faker->randomElement($conditions[$severity]);
        
        return [
            'title' => "Medical Condition: {$condition}",
            'description' => match($severity) {
                'low' => "Patient has {$condition}. Routine monitoring and lifestyle modifications recommended.",
                'medium' => "Active management required for {$condition}. Regular medication compliance needed.",
                'high' => "Serious medical condition: {$condition}. Requires ongoing specialist care.",
                'critical' => "Critical medical condition: {$condition}. Requires immediate attention for any changes."
            }
        ];
    }

    /**
     * Generate warning alert data.
     */
    private function generateWarningAlert($faker, string $severity): array
    {
        $warnings = [
            'low' => ['Annual screening due', 'Vaccination reminder', 'Routine follow-up needed'],
            'medium' => ['Drug interaction risk', 'Procedure precautions', 'Dietary restrictions'],
            'high' => ['Fall risk', 'Bleeding risk', 'Infection control precautions'],
            'critical' => ['DNR order', 'Isolation precautions', 'High suicide risk']
        ];

        $warning = $faker->randomElement($warnings[$severity]);
        
        return [
            'title' => "Warning: {$warning}",
            'description' => match($severity) {
                'low' => "Reminder: {$warning}. Please schedule appropriate follow-up.",
                'medium' => "Important warning: {$warning}. Requires attention and specific precautions.",
                'high' => "High-priority warning: {$warning}. Immediate precautions required.",
                'critical' => "Critical warning: {$warning}. Extreme caution required. Special protocols must be followed."
            }
        ];
    }
}
