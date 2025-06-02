<?php

namespace App\Repositories\Eloquent;

use App\Models\Appointment;
use App\Models\Patient;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class AppointmentRepository implements AppointmentRepositoryInterface
{
/**
     * Récupère tous les rendez-vous pour un patient spécifique.
     *
     * @param int $patientId
     * @return Collection<Appointment>
     */
    public function getAllByPatientId(int $patientId): Collection
    {
        Log::info("Récupération de tous les rendez-vous pour le patient ID: " . $patientId);
        
        // Obtenir l'ID utilisateur du patient
        $patient = Patient::find($patientId);
        if (!$patient) {
            Log::warning("Patient non trouvé: " . $patientId);
            return collect([]);
        }

$appointments = Appointment::where('patient_user_id', $patient->user_id)
            ->with(['doctor.doctorProfile', 'timeSlot']) 
            ->orderBy('appointment_datetime_start', 'desc')
            ->get();
            
        Log::info("Nombre de rendez-vous trouvés: " . $appointments->count());
        
        return $appointments;
    }
    
    /**
     * Récupère l'historique des rendez-vous pour un patient spécifique.
     *
     * @param int $patientId
     * @return Collection<Appointment>
     */
    public function getHistoryByPatientId(int $patientId): Collection
    {
       Log::info("Récupération de l'historique des rendez-vous pour le patient ID: " . $patientId);
        
        // Obtenir l'ID utilisateur du patient
        $patient = Patient::find($patientId);
        if (!$patient) {
            Log::warning("Patient non trouvé: " . $patientId);
            return collect([]);
        }
        
        $appointments = Appointment::where('patient_user_id', $patient->user_id)
            ->where(function ($query) {
                $query->where('appointment_datetime_start', '<', Date::now())
                      ->orWhereIn('status', ['completed', 'cancelled_by_patient', 'cancelled_by_clinic']);
            })
            ->with(['doctor.doctorProfile', 'timeSlot']) 
            ->orderBy('appointment_datetime_start', 'desc')
            ->get();
            
        Log::info("Nombre de rendez-vous historiques trouvés: " . $appointments->count());
        
        return $appointments;
    }

    /**
     * Récupère les rendez-vous à venir pour un patient spécifique.
     *
     * @param int $patientId
     * @return Collection<Appointment>
     */
    public function getUpcomingByPatientId(int $patientId): Collection
    {
        Log::info("Récupération des rendez-vous à venir pour le patient ID: " . $patientId);
        
        // Obtenir l'ID utilisateur du patient
        $patient = Patient::find($patientId);
        if (!$patient) {
            Log::warning("Patient non trouvé: " . $patientId);
            return collect([]);
        }
       $appointments = Appointment::where('patient_user_id', $patient->user_id)
            ->where('appointment_datetime_start', '>=', Date::now())
            ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_clinic'])
            ->with(['doctor.doctorProfile', 'timeSlot']) 
            ->orderBy('appointment_datetime_start', 'asc')
            ->get();
            
        Log::info("Nombre de rendez-vous à venir trouvés: " . $appointments->count());
        
        return $appointments;
    }
      /**
     * Récupère les rendez-vous filtrés par type ou statut pour un patient spécifique.
     *
     * @param int $patientId
     * @param string|null $type
     * @param string|null $status
     * @return Collection<Appointment>
     */
    public function getFilteredByPatientId(int $patientId, ?string $type = null, ?string $status = null): Collection
    {
        Log::info("Récupération des rendez-vous filtrés pour le patient ID: " . $patientId, [
            'type' => $type,
            'status' => $status
        ]);
        
        // Obtenir l'ID utilisateur du patient
        $patient = Patient::find($patientId);
        if (!$patient) {
            Log::warning("Patient non trouvé: " . $patientId);
            return collect([]);
        }
       $query = Appointment::where('patient_user_id', $patient->user_id);
        
        // Appliquer les filtres
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $appointments = $query->with(['doctor.doctorProfile', 'timeSlot'])
            ->orderBy('appointment_datetime_start', 'desc')
            ->get();
            
        Log::info("Nombre de rendez-vous filtrés trouvés: " . $appointments->count());
        
        return $appointments;
    }   

}