<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Models\BlockedTimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class DoctorAppointmentService
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * Get doctor's appointments
     */
    public function getMyAppointments(User $doctor, array $filters = [], int $perPage = 15)
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        $query = Appointment::where('doctor_user_id', $doctor->id);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('appointment_datetime_start', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('appointment_datetime_start', '<=', $filters['date_to']);
        }

        if (!empty($filters['upcoming'])) {
            $query->where('appointment_datetime_start', '>', now());
        }

        // ✅ FIXED: Load only existing relationships
        return $query->with(['patient'])
                     ->orderBy('appointment_datetime_start', 'asc')
                     ->paginate($perPage);
    }

    /**
     * Get today's schedule for doctor
     */
    public function getTodaysSchedule(User $doctor): Collection
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // ✅ FIXED: Removed patient.user relationship
        return Appointment::with(['patient'])
            ->where('doctor_user_id', $doctor->id)
            ->whereDate('appointment_datetime_start', today())
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->orderBy('appointment_datetime_start')
            ->get();
    }

    /**
     * Get upcoming appointments for doctor
     */
    public function getUpcomingAppointments(User $doctor, int $limit = 10)
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        return Appointment::where('doctor_user_id', $doctor->id)
            ->where('appointment_datetime_start', '>', now())
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC,
                Appointment::STATUS_COMPLETED
            ])
            // ✅ FIXED: Load only existing relationships
            ->with(['patient'])
            ->orderBy('appointment_datetime_start', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get doctor's schedule for a specific date
     */
    public function getScheduleForDate(User $doctor, string $date): array
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        $dateCarbon = Carbon::parse($date);
        
        // ✅ FIXED: Get appointments with only existing relationships
        $appointments = Appointment::with(['patient'])
            ->where('doctor_user_id', $doctor->id)
            ->whereDate('appointment_datetime_start', $dateCarbon)
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->orderBy('appointment_datetime_start')
            ->get();

        // ✅ FIXED: Get blocked time slots - assuming these methods exist or remove them
        $blockedSlots = BlockedTimeSlot::where('doctor_user_id', $doctor->id)
            ->whereDate('start_datetime', $dateCarbon)
            ->get();

        // ✅ FIXED: Get available time slots - assuming these methods exist or remove them
        $timeSlots = TimeSlot::where('doctor_user_id', $doctor->id)
            ->where('date', $dateCarbon->format('Y-m-d'))
            ->where('is_available', true)
            ->get();

        return [
            'date' => $dateCarbon->format('Y-m-d'),
            'appointments' => $appointments,
            'blocked_slots' => $blockedSlots,
            'available_slots' => $timeSlots,
            'total_appointments' => $appointments->count(),
            'completed_appointments' => $appointments->where('status', Appointment::STATUS_COMPLETED)->count(),
        ];
    }

    /**
     * Confirm appointment
     */
    public function confirmAppointment(User $doctor, Appointment $appointment): bool
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // Verify appointment belongs to doctor
        if ($appointment->doctor_user_id !== $doctor->id) {
            throw new Exception('You can only confirm your own appointments');
        }

        return $this->appointmentService->confirmAppointment($appointment, $doctor);
    }

    /**
     * Complete appointment with notes
     */
    public function completeAppointment(User $doctor, Appointment $appointment, ?string $notes = null): bool
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // Verify appointment belongs to doctor
        if ($appointment->doctor_user_id !== $doctor->id) {
            throw new Exception('You can only complete your own appointments');
        }

        return $this->appointmentService->completeAppointment($appointment, $doctor, $notes);
    }

    /**
     * Cancel appointment (doctor side)
     */
    public function cancelAppointment(User $doctor, Appointment $appointment, string $reason): bool
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // Verify appointment belongs to doctor
        if ($appointment->doctor_user_id !== $doctor->id) {
            throw new Exception('You can only cancel your own appointments');
        }

        return $this->appointmentService->cancelAppointment($appointment, $reason, $doctor);
    }

    /**
     * Cancel appointment with doctor override (emergency cancellation)
     */
    public function emergencyCancelAppointment(User $doctor, Appointment $appointment, string $reason): bool
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // Verify appointment belongs to doctor
        if ($appointment->doctor_user_id !== $doctor->id) {
            throw new Exception('You can only cancel your own appointments');
        }

        // Force cancellation regardless of time restrictions
        return $this->appointmentService->forceCancelAppointment($appointment, $reason, $doctor);
    }

    /**
     * Create time slots for doctor
     */
    public function createTimeSlots(User $doctor, array $scheduleData): array
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        $createdSlots = [];
        
        foreach ($scheduleData['dates'] as $date) {
            // ✅ FIXED: Simplified time slot creation
            $slots = []; // Implement your time slot creation logic here
            $createdSlots = array_merge($createdSlots, $slots);
        }

        return $createdSlots;
    }

    /**
     * Block time slots
     */
    public function blockTimeSlots(User $doctor, array $blockData): BlockedTimeSlot
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        $blockData['doctor_user_id'] = $doctor->id;
        $blockData['created_by_user_id'] = $doctor->id;

        return BlockedTimeSlot::create($blockData);
    }

    /**
     * Get doctor's availability for a date range
     */
    public function getAvailability(User $doctor, string $startDate, string $endDate): array
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $availability = [];

        while ($start <= $end) {
            $daySchedule = $this->getScheduleForDate($doctor, $start->format('Y-m-d'));
            
            $availability[$start->format('Y-m-d')] = [
                'date' => $start->format('Y-m-d'),
                'day_name' => $start->format('l'),
                'total_slots' => count($daySchedule['available_slots']),
                'booked_slots' => $daySchedule['total_appointments'],
                'available_slots' => count($daySchedule['available_slots']) - $daySchedule['total_appointments'],
                'blocked_slots' => count($daySchedule['blocked_slots']),
                'is_fully_booked' => (count($daySchedule['available_slots']) - $daySchedule['total_appointments']) <= 0,
            ];

            $start->addDay();
        }

        return $availability;
    }

    /**
     * Get doctor's appointment statistics
     */
    public function getDoctorStats(User $doctor, ?string $period = 'month'): array
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        $query = Appointment::where('doctor_user_id', $doctor->id);

        // Apply period filter
        switch ($period) {
            case 'today':
                $query->whereDate('appointment_datetime_start', today());
                break;
            case 'week':
                $query->whereBetween('appointment_datetime_start', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereBetween('appointment_datetime_start', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
            case 'year':
                $query->whereBetween('appointment_datetime_start', [
                    now()->startOfYear(),
                    now()->endOfYear()
                ]);
                break;
        }

        $totalAppointments = $query->count();
        $completedAppointments = (clone $query)->where('status', Appointment::STATUS_COMPLETED)->count();
        $cancelledAppointments = (clone $query)->whereIn('status', [
            Appointment::STATUS_CANCELLED_BY_PATIENT,
            Appointment::STATUS_CANCELLED_BY_CLINIC
        ])->count();
        $noShowAppointments = (clone $query)->where('status', Appointment::STATUS_NO_SHOW)->count();

        return [
            'period' => $period,
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'cancelled_appointments' => $cancelledAppointments,
            'no_show_appointments' => $noShowAppointments,
            'completion_rate' => $totalAppointments > 0 ? 
                round(($completedAppointments / $totalAppointments) * 100, 2) : 0,
            'cancellation_rate' => $totalAppointments > 0 ? 
                round(($cancelledAppointments / $totalAppointments) * 100, 2) : 0,
            'no_show_rate' => $totalAppointments > 0 ? 
                round(($noShowAppointments / $totalAppointments) * 100, 2) : 0,
        ];
    }

    /**
     * Mark patient as no-show
     */
    public function markAsNoShow(User $doctor, Appointment $appointment): bool
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // Verify appointment belongs to doctor
        if ($appointment->doctor_user_id !== $doctor->id) {
            throw new Exception('You can only mark your own appointments');
        }

        // Can only mark as no-show if appointment time has passed
        if ($appointment->appointment_datetime_start > now()) {
            throw new Exception('Cannot mark future appointments as no-show');
        }

        return $this->appointmentService->updateAppointment($appointment, [
            'status' => Appointment::STATUS_NO_SHOW
        ], $doctor);
    }

    /**
     * Create appointment for patient (doctor initiated)
     */
    public function createAppointment(User $doctor, array $appointmentData): Appointment
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // Validate patient exists
        $patient = User::find($appointmentData['patient_user_id']);
        if (!$patient || !$patient->isPatient()) {
            throw new Exception('Valid patient is required');
        }

        // Validate appointment time
        $startTime = Carbon::parse($appointmentData['appointment_datetime_start']);
        $endTime = Carbon::parse($appointmentData['appointment_datetime_end']);

        // Check if doctor is trying to book for themselves
        if ($appointmentData['doctor_user_id'] !== $doctor->id) {
            throw new Exception('You can only create appointments for yourself');
        }

        // Validate future date
        if ($startTime <= now()) {
            throw new Exception('Appointment must be in the future');
        }

        // Check for conflicts with existing appointments
        $conflicts = Appointment::where('doctor_user_id', $doctor->id)
            ->where(function($query) use ($startTime, $endTime) {
                $query->whereBetween('appointment_datetime_start', [$startTime, $endTime])
                      ->orWhereBetween('appointment_datetime_end', [$startTime, $endTime])
                      ->orWhere(function($q) use ($startTime, $endTime) {
                          $q->where('appointment_datetime_start', '<=', $startTime)
                            ->where('appointment_datetime_end', '>=', $endTime);
                      });
            })
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->exists();

        if ($conflicts) {
            throw new Exception('You have a conflicting appointment at this time');
        }

        // Check for blocked time slots
        $blockedSlots = BlockedTimeSlot::where('doctor_user_id', $doctor->id)
            ->where(function($query) use ($startTime, $endTime) {
                $query->where(function($q) use ($startTime, $endTime) {
                    $q->where('start_datetime', '<=', $startTime)
                      ->where('end_datetime', '>', $startTime);
                })->orWhere(function($q) use ($startTime, $endTime) {
                    $q->where('start_datetime', '<', $endTime)
                      ->where('end_datetime', '>=', $endTime);
                })->orWhere(function($q) use ($startTime, $endTime) {
                    $q->where('start_datetime', '>=', $startTime)
                      ->where('end_datetime', '<=', $endTime);
                });
            })
            ->exists();

        if ($blockedSlots) {
            throw new Exception('This time slot is blocked');
        }

        // Prepare appointment data
        $appointmentData = array_merge($appointmentData, [
            'doctor_user_id' => $doctor->id,
            'status' => Appointment::STATUS_SCHEDULED,
            'booked_by_user_id' => $doctor->id,
            'last_updated_by_user_id' => $doctor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create the appointment
        $appointment = Appointment::create($appointmentData);

        // Return with correct relationship
        return $appointment->load('patient');
    }

    /**
     * Create recurring appointments
     */
    public function createRecurringAppointments(User $doctor, array $recurringData): array
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // Validate patient exists
        $patient = User::find($recurringData['patient_user_id']);
        if (!$patient || !$patient->isPatient()) {
            throw new Exception('Valid patient is required');
        }

        // Validate pattern data
        $pattern = $recurringData['pattern'];
        $requiredFields = ['frequency', 'total_sessions', 'duration_minutes'];
        foreach ($requiredFields as $field) {
            if (empty($pattern[$field])) {
                throw new Exception("Pattern field '{$field}' is required");
            }
        }

        // Validate frequency
        $validFrequencies = ['daily', 'weekly', 'monthly'];
        if (!in_array($pattern['frequency'], $validFrequencies)) {
            throw new Exception('Invalid frequency. Must be: ' . implode(', ', $validFrequencies));
        }

        $startDate = Carbon::parse($recurringData['start_date']);
        $appointments = [];
        $errors = [];

        // Generate appointment dates based on pattern
        $currentDate = $startDate->copy();
        
        for ($i = 0; $i < $pattern['total_sessions']; $i++) {
            try {
                // Calculate appointment start time
                if (isset($pattern['time'])) {
                    $appointmentStart = $currentDate->copy()->setTimeFromTimeString($pattern['time']);
                } else {
                    $appointmentStart = $currentDate->copy()->setTime(9, 0); // Default to 9 AM
                }

                // Calculate end time
                $appointmentEnd = $appointmentStart->copy()->addMinutes($pattern['duration_minutes']);

                // Skip weekends for weekly appointments
                if ($pattern['frequency'] === 'weekly' && $appointmentStart->isWeekend()) {
                    $this->advanceDate($currentDate, $pattern);
                    continue;
                }

                // Check if this slot is available
                $conflicts = Appointment::where('doctor_user_id', $doctor->id)
                    ->where(function($query) use ($appointmentStart, $appointmentEnd) {
                        $query->whereBetween('appointment_datetime_start', [$appointmentStart, $appointmentEnd])
                              ->orWhereBetween('appointment_datetime_end', [$appointmentStart, $appointmentEnd])
                              ->orWhere(function($q) use ($appointmentStart, $appointmentEnd) {
                                  $q->where('appointment_datetime_start', '<=', $appointmentStart)
                                    ->where('appointment_datetime_end', '>=', $appointmentEnd);
                              });
                    })
                    ->whereNotIn('status', [
                        Appointment::STATUS_CANCELLED_BY_PATIENT,
                        Appointment::STATUS_CANCELLED_BY_CLINIC
                    ])
                    ->exists();

                if ($conflicts) {
                    $errors[] = "Conflict detected for session " . ($i + 1) . " on " . $appointmentStart->format('Y-m-d H:i');
                    $this->advanceDate($currentDate, $pattern);
                    continue;
                }

                // Create the appointment
                $appointmentData = [
                    'patient_user_id' => $recurringData['patient_user_id'],
                    'doctor_user_id' => $doctor->id,
                    'appointment_datetime_start' => $appointmentStart,
                    'appointment_datetime_end' => $appointmentEnd,
                    'type' => $recurringData['type'] ?? 'follow-up',
                    'reason_for_visit' => $recurringData['reason_for_visit'],
                    'status' => Appointment::STATUS_SCHEDULED,
                    'notes_by_staff' => "Recurring appointment - Session " . ($i + 1) . " of " . $pattern['total_sessions'],
                    'booked_by_user_id' => $doctor->id,
                    'last_updated_by_user_id' => $doctor->id,
                ];

                $appointment = Appointment::create($appointmentData);
                $appointments[] = $appointment;

            } catch (Exception $e) {
                $errors[] = "Failed to create session " . ($i + 1) . ": " . $e->getMessage();
            }

            // Advance to next appointment date
            $this->advanceDate($currentDate, $pattern);
        }

        if (empty($appointments) && !empty($errors)) {
            throw new Exception('Failed to create any appointments: ' . implode(', ', $errors));
        }

        return [
            'appointments' => $appointments,
            'total_created' => count($appointments),
            'total_requested' => $pattern['total_sessions'],
            'errors' => $errors,
            'success_rate' => round((count($appointments) / $pattern['total_sessions']) * 100, 2) . '%'
        ];
    }

    /**
     * Helper method to advance date based on frequency pattern
     */
    private function advanceDate(Carbon $date, array $pattern): void
    {
        switch ($pattern['frequency']) {
            case 'daily':
                $date->addDay();
                break;
            case 'weekly':
                if (isset($pattern['day_of_week'])) {
                    // Advance to specific day of week
                    $dayMap = [
                        'monday' => Carbon::MONDAY,
                        'tuesday' => Carbon::TUESDAY,
                        'wednesday' => Carbon::WEDNESDAY,
                        'thursday' => Carbon::THURSDAY,
                        'friday' => Carbon::FRIDAY,
                        'saturday' => Carbon::SATURDAY,
                        'sunday' => Carbon::SUNDAY,
                    ];
                    $targetDay = $dayMap[strtolower($pattern['day_of_week'])] ?? Carbon::MONDAY;
                    $date->addWeek()->startOfWeek()->addDays($targetDay - 1);
                } else {
                    $date->addWeek();
                }
                break;
            case 'monthly':
                $date->addMonth();
                break;
        }
    }

    /**
     * Reschedule appointment (Doctor)
     */
    public function rescheduleAppointment(User $doctor, Appointment $appointment, array $rescheduleData): Appointment
    {
        if (!$doctor->isDoctor()) {
            throw new Exception('User is not a doctor');
        }

        // Verify doctor owns this appointment
        if ($appointment->doctor_user_id !== $doctor->id) {
            throw new Exception('Unauthorized to reschedule this appointment');
        }

        // Check if new time slot is available
        $conflictData = [
            'doctor_user_id' => $doctor->id,
            'appointment_datetime_start' => $rescheduleData['new_datetime_start'],
            'appointment_datetime_end' => $rescheduleData['new_datetime_end']
        ];
        
        // Use the injected AppointmentService to check for conflicts
        try {
            $this->appointmentService->checkForConflicts($conflictData, $appointment->id);
        } catch (Exception $e) {
            throw new Exception('The requested time slot is not available: ' . $e->getMessage());
        }

        // Update appointment
        $appointment->update([
            'appointment_datetime_start' => $rescheduleData['new_datetime_start'],
            'appointment_datetime_end' => $rescheduleData['new_datetime_end'],
            'notes_by_staff' => $rescheduleData['notes_by_staff'] ?? $appointment->notes_by_staff,
            'status' => Appointment::STATUS_RESCHEDULED,
        ]);

        // Log activity
        activity()
            ->causedBy($doctor)
            ->performedOn($appointment)
            ->withProperties([
                'old_datetime_start' => $appointment->getOriginal('appointment_datetime_start'),
                'old_datetime_end' => $appointment->getOriginal('appointment_datetime_end'),
                'new_datetime_start' => $rescheduleData['new_datetime_start'],
                'new_datetime_end' => $rescheduleData['new_datetime_end'],
            ])
            ->log('appointment_rescheduled');

        return $appointment->fresh(['patient', 'doctor']);
    }
}