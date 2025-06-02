<?php
 
namespace App\Services;

use App\DTOs\AppointmentDto;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;

class GetAppointmentHistoryService
{
    private AppointmentRepositoryInterface $appointmentRepository;

    public function __construct(AppointmentRepositoryInterface $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
        Log::info("GetAppointmentHistoryService initialized");
    }

    public function execute(int $patientId): Collection
    {
        // Obtenir l'historique des rendez-vous
        $appointments = $this->appointmentRepository->getHistoryByPatientId($patientId);
        
        // Mapper les rendez-vous en DTOs
        return $appointments->map(function (Appointment $appointment) {
            try {
                // Format date
                $formattedDate = Carbon::parse($appointment->appointment_datetime_start)->format('F jS, Y');
                
                // Format time
                $formattedTime = Carbon::parse($appointment->appointment_datetime_start)->format('H:i') .
                             ' - ' .
                             Carbon::parse($appointment->appointment_datetime_end)->format('H:i');
                
                // Format status
                $statusDisplay = ucfirst(str_replace('_', ' ', $appointment->status));
                
                // Get doctor info
                $doctorName = 'N/A';
                $doctorSpecialty = 'General Medicine';
                
                if ($appointment->doctor) {
                    $doctorName = $appointment->doctor->name;
                    
                    if ($appointment->doctor->doctorProfile) {
                        $doctorSpecialty = $appointment->doctor->doctorProfile->specialty;
                    }
                }
                
                // Get appointment type
                $appointmentType = $appointment->type ?: 'Regular';
                
                // Location (can be customized based on your data model)
                // $location = 'Medical Center';
                
                return new AppointmentDTO(
                    id: $appointment->id,
                    date: $formattedDate,
                    time: $formattedTime,
                    reason: $appointment->reason_for_visit,
                    status: $statusDisplay,
                    doctorName: $doctorName,
                    doctorSpecialty: $doctorSpecialty,
                    cancelReason: $appointment->cancellation_reason,
                    // location: $location,
                    followUp: $appointment->type === 'follow-up',
                    notes: $appointment->notes_by_patient ? [$appointment->notes_by_patient] : [],
                    type: $appointmentType
                );
            } catch (\Exception $e) {
                Log::error("Error mapping appointment to DTO: " . $e->getMessage(), [
                    'appointment_id' => $appointment->id
                ]);
                return null;
            }
        })->filter()->values(); // Remove any null values from mapping errors
    }
}