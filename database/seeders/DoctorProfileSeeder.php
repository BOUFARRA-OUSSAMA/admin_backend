<?php
// filepath: c:\Users\Microsoft\Desktop\project\admin_backend\database\seeders\DoctorProfileSeeder.php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Role;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DoctorProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $this->command->info('Creating doctor profiles...');
        
        // Récupérer le rôle de médecin
        $doctorRole = Role::where('code', 'doctor')->first();
        
        if (!$doctorRole) {
            $this->command->error('Doctor role not found. Run RoleSeeder first.');
            return;
        }
        
        // Récupérer tous les utilisateurs ayant le rôle de médecin
        $doctorUsers = User::whereHas('roles', function($query) use ($doctorRole) {
            $query->where('roles.id', $doctorRole->id);
        })->get();
        
        if ($doctorUsers->isEmpty()) {
            // Vérifier si le médecin de test existe
            $testDoctorUser = User::where('email', 'doctor@example.com')->first();
            
            if ($testDoctorUser) {
                $doctorUsers = collect([$testDoctorUser]);
            } else {
                $this->command->error('No doctor users found. Run AdminUserSeeder first.');
                return;
            }
        }
        
        // Spécialités médicales communes
        $specialties = [
            'Médecine générale',
            'Cardiologie',
            'Dermatologie',
            'Gastroentérologie',
            'Neurologie',
            'Ophtalmologie',
            'Orthopédie',
            'Pédiatrie',
            'Psychiatrie',
            'Radiologie',
            'Urologie',
            'Gynécologie',
            'Endocrinologie',
            'Pneumologie',
            'Néphrologie',
            'Rhumatologie',
            'Chirurgie générale'
        ];
        
        // Établissements d'éducation médicale
        $educationalInstitutions = [
            'Université de Paris',
            'Université de Lyon',
            'Université de Bordeaux',
            'Université de Marseille',
            'Université de Toulouse',
            'Université de Strasbourg',
            'Université de Montpellier',
            'Université de Lille',
            'Université de Nantes',
            'Université de Nice',
            'Harvard Medical School',
            'Johns Hopkins University',
            'Stanford University',
            'Yale University',
            'Oxford University',
            'Cambridge University'
        ];
        
        $createdCount = 0;
        $updatedCount = 0;
        
        foreach ($doctorUsers as $user) {
            // Vérifier si un profil existe déjà
            $doctorProfile = Doctor::where('user_id', $user->id)->first();
            
            if ($doctorProfile) {
                // Mettre à jour le profil existant
                $doctorProfile->update([
                    'specialty' => $faker->randomElement($specialties),
                    'license_number' => 'LIC-' . $faker->numerify('#####'),
                    'education' => $faker->randomElement($educationalInstitutions) . ', ' . $faker->year(),
                    'experience' => $faker->numberBetween(1, 30) . ' ans d\'expérience',
                    'availability_notes' => $faker->randomElement([
                        'Disponible les lundis et mercredis',
                        'Consultations uniquement les matins',
                        'Disponible tous les jours sauf le vendredi',
                        'Consultations sur rendez-vous uniquement',
                        'Disponible pour urgences le week-end'
                    ]),
                    'is_active' => true
                ]);
                
                $updatedCount++;
            } else {
                // Créer un nouveau profil
                Doctor::create([
                    'user_id' => $user->id,
                    'specialty' => $faker->randomElement($specialties),
                    'license_number' => 'LIC-' . $faker->numerify('#####'),
                    'education' => $faker->randomElement($educationalInstitutions) . ', ' . $faker->year(),
                    'experience' => $faker->numberBetween(1, 30) . ' ans d\'expérience',
                    'availability_notes' => $faker->randomElement([
                        'Disponible les lundis et mercredis',
                        'Consultations uniquement les matins',
                        'Disponible tous les jours sauf le vendredi',
                        'Consultations sur rendez-vous uniquement',
                        'Disponible pour urgences le week-end'
                    ]),
                    'is_active' => true
                ]);
                
                $createdCount++;
            }
        }
        
        $this->command->info("Created $createdCount new doctor profiles and updated $updatedCount existing profiles.");
    }
}