<?php
namespace App\DTOs;

class AppointmentDTO
{
    public function __construct(
        public int $id,
        public string $date, // Formatted e.g., "May 10th, 2023"
        public string $time, // Formatted e.g., "09:00 - 09:30"
        public ?string $reason,
        public string $status,
        public ?string $doctorName, // Doctor's name or "N/A" if not available
        public ?string $doctorSpecialty = null, // Ajout: spécialité du médecin
        public ?string $cancelReason = null, // Ajout: raison d'annulation
        // public ?string $location = null, // Ajout: emplacement du rendez-vous
        public ?bool $followUp = null, // Ajout: si c'est un suivi
        public ?array $notes = null, // Ajout: notes du rendez-vous
        public ?string $type = null // Ajout du type de rendez-vous
        ) {}
}
