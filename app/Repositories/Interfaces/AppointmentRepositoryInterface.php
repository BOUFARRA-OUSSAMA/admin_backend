<?php
// filepath: c:\Users\Microsoft\Desktop\project\admin_backend\app\Repositories\Interfaces\AppointmentRepositoryInterface.php

namespace App\Repositories\Interfaces;

use App\Models\Appointment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AppointmentRepositoryInterface
{
    /**
     * Récupère tous les rendez-vous pour un patient spécifique.
     *
     * @param int $patientId
     * @return Collection<Appointment>
     */
    public function getAllByPatientId(int $patientId): Collection;
    
    /**
     * Récupère l'historique des rendez-vous pour un patient spécifique.
     *
     * @param int $patientId
     * @return Collection<Appointment>
     */
    public function getHistoryByPatientId(int $patientId): Collection;

    /**
     * Récupère les rendez-vous à venir pour un patient spécifique.
     *
     * @param int $patientId
     * @return Collection<Appointment>
     */
    public function getUpcomingByPatientId(int $patientId): Collection;
    
    /**
     * Récupère les rendez-vous filtrés par type ou statut pour un patient spécifique.
     *
     * @param int $patientId
     * @param string|null $type
     * @param string|null $status
     * @return Collection<Appointment>
     */
    public function getFilteredByPatientId(int $patientId, ?string $type = null, ?string $status = null): Collection;
}