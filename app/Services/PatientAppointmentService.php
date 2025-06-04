<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\BlockedTimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Exception;

class PatientAppointmentService
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * Get patient's appointments
     */
    public function getMyAppointments(User $patient, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        $filters['patient_id'] = $patient->id;
        
        return $this->appointmentService->getAppointments($filters, $perPage);
    }

    /**
     * Get upcoming appointments for patient
     */
    public function getUpcomingAppointments(User $patient): Collection
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        // ✅ REMOVED: timeSlot relationship
        return Appointment::with(['doctor'])
            ->where('patient_user_id', $patient->id)
            ->where('appointment_datetime_start', '>', now())
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED
            ])
            ->orderBy('appointment_datetime_start')
            ->get();
    }

    /**
     * Get today's appointments for patient
     */
    public function getTodaysAppointments(User $patient): Collection
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        // ✅ REMOVED: timeSlot relationship
        return Appointment::with(['doctor'])
            ->where('patient_user_id', $patient->id)
            ->whereDate('appointment_datetime_start', today())
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->orderBy('appointment_datetime_start')
            ->get();
    }

    /**
     * Get next appointment for patient
     */
    public function getNextAppointment(User $patient): ?Appointment
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        // ✅ REMOVED: timeSlot relationship
        return Appointment::with(['doctor'])
            ->where('patient_user_id', $patient->id)
            ->where('appointment_datetime_start', '>', now())
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED
            ])
            ->orderBy('appointment_datetime_start')
            ->first();
    }

    /**
     * Get appointment history for patient
     */
    public function getAppointmentHistory(User $patient, int $perPage = 15): LengthAwarePaginator
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        // ✅ REMOVED: timeSlot relationship
        return Appointment::with(['doctor'])
            ->where('patient_user_id', $patient->id)
            ->where('appointment_datetime_start', '<', now())
            ->orderBy('appointment_datetime_start', 'desc')
            ->paginate($perPage);
    }

    /**
     * Book new appointment for patient
     */
    public function bookAppointment(User $patient, array $appointmentData): Appointment
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        // Add patient ID to the data
        $appointmentData['patient_user_id'] = $patient->id;

        // Validate booking rules
        $this->validatePatientBookingRules($patient, $appointmentData);

        // Set default status for patient bookings
        $appointmentData['status'] = Appointment::STATUS_SCHEDULED;

        return $this->appointmentService->createAppointment($appointmentData, $patient);
    }

    /**
     * Cancel patient's appointment
     */
    public function cancelMyAppointment(User $patient, Appointment $appointment, string $reason): bool
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        // Verify appointment belongs to patient
        if ($appointment->patient_user_id !== $patient->id) {
            throw new Exception('You can only cancel your own appointments');
        }

        // Check if appointment can be cancelled
        if (!$appointment->canBeCancelled()) {
            throw new Exception('This appointment cannot be cancelled. Please contact the clinic.');
        }

        // Additional patient-specific cancellation rules
        $this->validatePatientCancellationRules($appointment);

        return $this->appointmentService->cancelAppointment($appointment, $reason, $patient);
    }

    /**
     * Reschedule patient's appointment
     */
    public function rescheduleMyAppointment(User $patient, Appointment $appointment, array $newTimeData): Appointment
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        // Verify appointment belongs to patient
        if ($appointment->patient_user_id !== $patient->id) {
            throw new Exception('You can only reschedule your own appointments');
        }

        // Check if appointment can be rescheduled
        if (!$appointment->canBeRescheduled()) {
            throw new Exception('This appointment cannot be rescheduled. Please contact the clinic.');
        }

        // Validate rescheduling rules
        $this->validatePatientReschedulingRules($patient, $appointment, $newTimeData);

        // Update appointment with new time
        $updateData = [
            'appointment_datetime_start' => $newTimeData['appointment_datetime_start'],
            'appointment_datetime_end' => $newTimeData['appointment_datetime_end'] ?? 
                Carbon::parse($newTimeData['appointment_datetime_start'])->addMinutes(30),
        ];

        return $this->appointmentService->updateAppointment($appointment, $updateData, $patient);
    }

    /**
     * Get available doctors
     */
    public function getAvailableDoctors(?string $specialty = null, ?string $location = null): Collection
    {
        $query = User::whereHas('roles', function($roleQuery) {
            $roleQuery->where('code', 'doctor');
        });

        // Filter by specialty if provided
        if ($specialty) {
            $query->whereHas('doctor', function($doctorQuery) use ($specialty) {
                $doctorQuery->where('specialty', 'like', "%{$specialty}%");
            });
        }

        // Filter by location if provided
        if ($location) {
            $query->whereHas('doctor', function($doctorQuery) use ($location) {
                $doctorQuery->where('clinic_address', 'like', "%{$location}%")
                           ->orWhere('city', 'like', "%{$location}%");
            });
        }

        return $query->with(['doctor'])
                     ->get();
    }

    /**
     * Get available appointment slots using doctor's working hours (NO TimeSlot table)
     */
    public function getAvailableSlots(int $doctorId, string $date): array
    {
        $targetDate = Carbon::parse($date);
        $dayName = strtolower($targetDate->format('l')); // monday, tuesday, etc.
        
        // Get doctor with working hours
        $doctor = User::with('doctor')->where('id', $doctorId)
            ->whereHas('roles', function($query) {
                $query->where('code', 'doctor');
            })
            ->first();

        if (!$doctor || !$doctor->doctor) {
            throw new Exception('Doctor not found');
        }

        // ✅ USE: Doctor's working hours from database
        $workingHours = $doctor->doctor->working_hours;
        
        // Check if doctor works on this day
        if (!isset($workingHours[$dayName]) || $workingHours[$dayName] === null) {
            return [
                'date' => $targetDate->format('Y-m-d'),
                'doctor_id' => $doctorId,
                'doctor_name' => $doctor->name,
                'available_slots' => [],
                'message' => 'Doctor does not work on ' . ucfirst($dayName),
                'is_working_day' => false
            ];
        }

        $daySchedule = $workingHours[$dayName];
        $startTime = $daySchedule[0]; // "09:00"
        $endTime = $daySchedule[1];   // "17:00"
        
        // Get existing appointments
        $existingAppointments = Appointment::where('doctor_user_id', $doctorId)
            ->whereDate('appointment_datetime_start', $targetDate->format('Y-m-d'))
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->get();

        // Get blocked slots
        $blockedSlots = BlockedTimeSlot::where('doctor_user_id', $doctorId)
            ->whereDate('start_datetime', $targetDate->format('Y-m-d'))
            ->get();

        // ✅ GENERATE: Create slots based on doctor's working hours
        $availableSlots = [];
        $slotDuration = 30; // minutes
        $currentTime = Carbon::parse($targetDate->format('Y-m-d') . ' ' . $startTime);
        $endTimeCarbon = Carbon::parse($targetDate->format('Y-m-d') . ' ' . $endTime);

        // Add lunch break logic (12:00-13:00)
        $lunchStart = Carbon::parse($targetDate->format('Y-m-d') . ' 12:00');
        $lunchEnd = Carbon::parse($targetDate->format('Y-m-d') . ' 13:00');

        while ($currentTime < $endTimeCarbon) {
            $slotEnd = $currentTime->copy()->addMinutes($slotDuration);

            // Skip lunch break
            if ($currentTime >= $lunchStart && $currentTime < $lunchEnd) {
                $currentTime = $lunchEnd->copy();
                continue;
            }

            // Check for conflicts
            $isBooked = $existingAppointments->contains(function ($appointment) use ($currentTime, $slotEnd) {
                return $appointment->appointment_datetime_start < $slotEnd && 
                       $appointment->appointment_datetime_end > $currentTime;
            });

            $isBlocked = $blockedSlots->contains(function ($blocked) use ($currentTime, $slotEnd) {
                return $blocked->start_datetime < $slotEnd && 
                       $blocked->end_datetime > $currentTime;
            });

            // Add available slot
            if (!$isBooked && !$isBlocked && $slotEnd <= $endTimeCarbon) {
                $availableSlots[] = [
                    'start_time' => $currentTime->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'start_datetime' => $currentTime->format('Y-m-d H:i:s'),
                    'end_datetime' => $slotEnd->format('Y-m-d H:i:s'),
                    'duration_minutes' => $slotDuration
                ];
            }

            $currentTime->addMinutes($slotDuration);
        }

        return [
            'date' => $targetDate->format('Y-m-d'),
            'day_name' => ucfirst($dayName),
            'doctor_id' => $doctorId,
            'doctor_name' => $doctor->name,
            'working_hours' => [
                'start' => $startTime,
                'end' => $endTime,
                'is_working_day' => true
            ],
            'available_slots' => $availableSlots,
            'total_available' => count($availableSlots),
            'booked_count' => $existingAppointments->count(),
            'blocked_count' => $blockedSlots->count()
        ];
    }

    /**
     * Get patient's appointment statistics
     */
    public function getPatientStats(User $patient): array
    {
        if (!$patient->isPatient()) {
            throw new Exception('User is not a patient');
        }

        $totalAppointments = Appointment::where('patient_user_id', $patient->id)->count();
        $completedAppointments = Appointment::where('patient_user_id', $patient->id)
            ->where('status', Appointment::STATUS_COMPLETED)->count();
        $cancelledAppointments = Appointment::where('patient_user_id', $patient->id)
            ->whereIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])->count();
        $upcomingAppointments = Appointment::where('patient_user_id', $patient->id)
            ->where('appointment_datetime_start', '>', now())
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED
            ])->count();

        return [
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'cancelled_appointments' => $cancelledAppointments,
            'upcoming_appointments' => $upcomingAppointments,
            'cancellation_rate' => $totalAppointments > 0 ? 
                round(($cancelledAppointments / $totalAppointments) * 100, 2) : 0,
            'completion_rate' => $totalAppointments > 0 ? 
                round(($completedAppointments / $totalAppointments) * 100, 2) : 0,
        ];
    }

    /**
     * Private validation methods
     */
    private function validatePatientBookingRules(User $patient, array $appointmentData): void
    {
        // ✅ GET: Doctor and their appointment limit
        $doctor = Doctor::where('user_id', $appointmentData['doctor_user_id'] ?? $appointmentData['doctor_id'])->first();
        
        if (!$doctor) {
            throw new Exception('Doctor not found');
        }

        // ✅ UPDATED: Use doctor's specific limit instead of hard-coded 5
        $maxAppointments = $doctor->getMaxPatientAppointments();

        // Check if patient has too many upcoming appointments WITH THIS SPECIFIC DOCTOR
        $upcomingCount = Appointment::where('patient_user_id', $patient->id)
            ->where('doctor_user_id', $doctor->user_id) // ✅ IMPORTANT: Per-doctor limit
            ->where('appointment_datetime_start', '>', now())
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED
            ])
            ->count();

        if ($upcomingCount >= $maxAppointments) {
            throw new Exception("You cannot have more than {$maxAppointments} upcoming appointments with Dr. {$doctor->user->name}");
        }

        // Check if patient is trying to book multiple appointments on the same day WITH SAME DOCTOR
        $appointmentDate = Carbon::parse($appointmentData['appointment_datetime_start'])->toDateString();
        $sameDay = Appointment::where('patient_user_id', $patient->id)
            ->where('doctor_user_id', $doctor->user_id) // ✅ IMPORTANT: Same doctor check
            ->whereDate('appointment_datetime_start', $appointmentDate)
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC,
                Appointment::STATUS_COMPLETED
            ])
            ->exists();

        if ($sameDay) {
            throw new Exception("You already have an appointment with {$doctor->user->name} on this date");
        }

        // Check minimum booking notice (2 hours)
        $appointmentTime = Carbon::parse($appointmentData['appointment_datetime_start']);
        if ($appointmentTime->lessThan(now()->addHours(2))) {
            throw new Exception('Appointments must be booked at least 2 hours in advance');
        }
    }

    private function validatePatientCancellationRules(Appointment $appointment): void
    {
        // Patients must cancel at least 24 hours in advance
        if ($appointment->appointment_datetime_start->lessThan(now()->addHours(24))) {
            throw new Exception('Appointments must be cancelled at least 24 hours in advance');
        }

        // Check if patient has cancelled too many appointments recently
        $recentCancellations = Appointment::where('patient_user_id', $appointment->patient_user_id)
            ->where('status', Appointment::STATUS_CANCELLED_BY_PATIENT)
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        if ($recentCancellations >= 3) {
            throw new Exception('You have cancelled too many appointments recently. Please contact the clinic.');
        }
    }

    private function validatePatientReschedulingRules(User $patient, Appointment $appointment, array $newTimeData): void
    {
        // Patients can only reschedule once
        $rescheduleCount = Appointment::where('patient_user_id', $patient->id)
            ->where('id', $appointment->id)
            ->whereNotNull('updated_at')
            ->where('updated_at', '!=', $appointment->created_at)
            ->count();

        if ($rescheduleCount > 0) {
            throw new Exception('Appointments can only be rescheduled once. Please contact the clinic for further changes.');
        }

        // Same validation as booking
        $this->validatePatientBookingRules($patient, [
            'appointment_datetime_start' => $newTimeData['appointment_datetime_start']
        ]);
    }
}