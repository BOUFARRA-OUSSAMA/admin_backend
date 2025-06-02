<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\Role;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $this->command->info('Creating appointments...');
        
        // Récupérer les patients
        $patients = Patient::all();
        if ($patients->isEmpty()) {
            // Vérifie si le patient de test existe (créé par AdminUserSeeder)
            $testPatientUser = User::where('email', 'patient@example.com')->first();
            
            if ($testPatientUser) {
                // Crée un patient s'il n'existe pas déjà
                $patient = Patient::firstOrCreate(
                    ['user_id' => $testPatientUser->id],
                    [
                        'registration_date' => now()->format('Y-m-d'),
                        'medical_record_number' => 'MRN-' . str_pad(1, 6, '0', STR_PAD_LEFT)
                    ]
                );
                $patients = collect([$patient]);
                $this->command->info('Created patient record for test patient');
            } else {
                $this->command->error('No patients found and test patient does not exist. Run AdminUserSeeder first.');
                return;
            }
        }
        
        // Récupérer les médecins
        $doctorRole = Role::where('code', 'doctor')->first();
        if (!$doctorRole) {
            $this->command->error('Doctor role not found. Run RoleSeeder first.');
            return;
        }
        
        $doctors = User::whereHas('roles', function($query) use ($doctorRole) {
            $query->where('roles.id', $doctorRole->id);
        })->get();
        
        if ($doctors->isEmpty()) {
            // Vérifie si le médecin de test existe (créé par AdminUserSeeder)
            $testDoctorUser = User::where('email', 'doctor@example.com')->first();
            
            if ($testDoctorUser) {
                $doctors = collect([$testDoctorUser]);
            } else {
                $this->command->error('No doctors found. Run AdminUserSeeder first.');
                return;
            }
        }
        
        // Récupérer les créneaux horaires
        $timeSlots = TimeSlot::where('is_active', true)->get();
        if ($timeSlots->isEmpty()) {
            $this->command->error('No time slots found. Run TimeSlotSeeder first.');
            return;
        }
        
        // Statuts possibles pour les rendez-vous
        $statuses = ['pending', 'scheduled', 'completed', 'cancelled_by_patient', 'cancelled_by_clinic'];
        
        // Raisons courantes de rendez-vous
        $reasons = [
            'Consultation générale',
            'Suivi de traitement',
            'Douleurs abdominales',
            'Examen annuel',
            'Vaccin',
            'Mal de tête',
            'Problèmes dermatologiques',
            'Fièvre',
            'Renouvellement d\'ordonnance',
            'Toux persistante'
        ];
        
        // Générer des rendez-vous pour chaque patient
        $appointmentCount = 0;
        
        foreach ($patients as $patient) {
            // Créer entre 3 et 8 rendez-vous par patient
            $appointmentsPerPatient = rand(3, 8);
            
            for ($i = 0; $i < $appointmentsPerPatient; $i++) {
                $doctor = $doctors->random();
                $timeSlot = $timeSlots->random();
                
                // Dates réparties entre passé, présent et futur
                $daysOffset = rand(-60, 60);
                $appointmentDate = Carbon::now()->addDays($daysOffset);
                
                // Calculer les dates de début et de fin
                $startTime = Carbon::parse($timeSlot->start_time)->setDate(
                    $appointmentDate->year, 
                    $appointmentDate->month, 
                    $appointmentDate->day
                );
                
                $endTime = Carbon::parse($timeSlot->end_time)->setDate(
                    $appointmentDate->year, 
                    $appointmentDate->month, 
                    $appointmentDate->day
                );
                
                // Déterminer le statut en fonction de la date
                $status = 'scheduled';
                if ($daysOffset < -7) {
                    // Les rendez-vous plus anciens que 7 jours sont soit terminés soit annulés
                    $status = $faker->randomElement(['completed', 'cancelled_by_patient']);
                } elseif ($daysOffset < 0) {
                    // Les rendez-vous passés mais récents sont généralement terminés
                    $status = 'completed';
                } elseif ($daysOffset > 14) {
                    // Les rendez-vous futurs lointains sont généralement en attente
                    $status = 'pending';
                }
                
                // Raison d'annulation si applicable
                $cancelReason = null;
                if ($status === 'cancelled_by_patient' || $status === 'cancelled_by_clinic') {
                    $cancelReasons = [
                        'Patient unavailable',
                        'Patient requested cancellation',
                        'Doctor unavailable',
                        'Emergency situation',
                        'Patient sick with other condition'
                    ];
                    $cancelReason = $faker->randomElement($cancelReasons);
                }
                
                // Type de rendez-vous
                $appointmentType = $faker->randomElement(['initial', 'follow-up', 'consultation']);
                
                // Déterminer le type de rendez-vous (suivi pour certains)
                if ($i > 0 && rand(0, 10) > 7) {
                    $appointmentType = 'follow-up';
                }
                
                try {
                    $appointment = new Appointment();
                    $appointment->patient_user_id = $patient->user_id;
                    $appointment->doctor_user_id = $doctor->id;
                    $appointment->time_slot_id = $timeSlot->id;
                    $appointment->appointment_datetime_start = $startTime;
                    $appointment->appointment_datetime_end = $endTime;
                    $appointment->type = $appointmentType;
                    $appointment->reason_for_visit = $faker->randomElement($reasons);
                    $appointment->status = $status;
                    $appointment->cancellation_reason = $cancelReason;
                    $appointment->notes_by_patient = $faker->boolean(30) ? $faker->sentence(10) : null;
                    $appointment->booked_by_user_id = $patient->user_id;
                    $appointment->created_at = Carbon::now()->subDays(abs($daysOffset) + rand(1, 10));
                    $appointment->updated_at = Carbon::now()->subDays(abs($daysOffset) + rand(0, 3));
                    $appointment->save();
                    
                    $appointmentCount++;
                } catch (\Exception $e) {
                    $this->command->error("Error creating appointment: " . $e->getMessage());
                }
            }
        }
        
        $this->command->info("Created $appointmentCount appointments for testing.");
    }
}