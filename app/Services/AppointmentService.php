<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Models\BlockedTimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AppointmentService
{
    /**
     * Get appointments with filters and pagination
     */
    public function getAppointments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // ✅ REMOVED: 'timeSlot' from with() clause
        $query = Appointment::with(['patient', 'doctor']);

        // Apply filters
        if (!empty($filters['patient_id'])) {
            $query->where('patient_user_id', $filters['patient_id']);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_user_id', $filters['doctor_id']);
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('appointment_datetime_start', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('appointment_datetime_start', '<=', $filters['date_to']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('appointment_datetime_start', $filters['date']);
        }

        if (!empty($filters['upcoming'])) {
            $query->where('appointment_datetime_start', '>=', now());
        }

        if (!empty($filters['today'])) {
            $query->whereDate('appointment_datetime_start', today());
        }

        if (!empty($filters['this_week'])) {
            $query->whereBetween('appointment_datetime_start', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        }

        // Search in patient name, doctor name, or reason
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('doctor', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%");
                })
                ->orWhere('reason_for_visit', 'like', "%{$search}%");
            });
        }

        // Default ordering
        $query->orderBy('appointment_datetime_start', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Create new appointment
     */
    public function createAppointment(array $data, User $createdBy): Appointment
    {
        DB::beginTransaction();
        
        try {
            // Validate the data
            $this->validateAppointmentData($data);
            
            // Check for conflicts
            $this->checkForConflicts($data);
            
            // ✅ REMOVED: findOrCreateTimeSlot logic
            
            // Create appointment
            $appointment = Appointment::create([
                'patient_user_id' => $data['patient_user_id'] ?? $data['patient_id'],
                'doctor_user_id' => $data['doctor_user_id'] ?? $data['doctor_id'],
                // ✅ REMOVED: time_slot_id
                'appointment_datetime_start' => $data['appointment_datetime_start'],
                'appointment_datetime_end' => $data['appointment_datetime_end'] ?? 
                    Carbon::parse($data['appointment_datetime_start'])->addMinutes(30),
                'type' => $data['type'] ?? 'consultation',
                'reason_for_visit' => $data['reason_for_visit'] ?? $data['reason'] ?? 'General consultation',
                'status' => $data['status'] ?? Appointment::STATUS_SCHEDULED,
                'notes_by_patient' => $data['notes_by_patient'] ?? $data['patient_notes'] ?? null,
                'notes_by_staff' => $data['notes_by_staff'] ?? $data['staff_notes'] ?? null,
                'booked_by_user_id' => $createdBy->id,
                'last_updated_by_user_id' => $createdBy->id,
            ]);

            // Log the activity
            activity()
                ->causedBy($createdBy)
                ->performedOn($appointment)
                ->withProperties([
                    'patient_id' => $data['patient_user_id'] ?? $data['patient_id'],
                    'doctor_id' => $data['doctor_user_id'] ?? $data['doctor_id'],
                    'appointment_time' => $data['appointment_datetime_start'],
                ])
                ->log('appointment_created');

            DB::commit();
            
            // ✅ REMOVED: 'timeSlot' from load() clause
            return $appointment->load(['patient', 'doctor']);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create appointment: ' . $e->getMessage(), [
                'data' => $data,
                'created_by' => $createdBy->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update appointment
     */
    public function updateAppointment(Appointment $appointment, array $data, User $updatedBy): Appointment
    {
        DB::beginTransaction();
        
        try {
            // Store original data for comparison
            $originalData = $appointment->toArray();
            
            // Validate new data (pass existing appointment for context)
            $this->validateAppointmentData($data, $appointment->id, $appointment);
            
            // Check for conflicts if time/date changed
            if ($this->hasTimeChanged($appointment, $data)) {
                // Ensure we have doctor ID for conflict checking
                $conflictCheckData = $data;
                if (!isset($conflictCheckData['doctor_user_id']) && !isset($conflictCheckData['doctor_id'])) {
                    $conflictCheckData['doctor_user_id'] = $appointment->doctor_user_id;
                }
                $this->checkForConflicts($conflictCheckData, $appointment->id);
            }
            
            // Update appointment
            $appointment->update([
                'appointment_datetime_start' => $data['appointment_datetime_start'] ?? $appointment->appointment_datetime_start,
                'appointment_datetime_end' => $data['appointment_datetime_end'] ?? $appointment->appointment_datetime_end,
                'type' => $data['type'] ?? $appointment->type,
                'reason_for_visit' => $data['reason_for_visit'] ?? $data['reason'] ?? $appointment->reason_for_visit,
                'status' => $data['status'] ?? $appointment->status,
                'notes_by_patient' => $data['notes_by_patient'] ?? $data['patient_notes'] ?? $appointment->notes_by_patient,
                'notes_by_staff' => $data['notes_by_staff'] ?? $data['staff_notes'] ?? $appointment->notes_by_staff,
                'last_updated_by_user_id' => $updatedBy->id,
            ]);

            // Log the activity
            activity()
                ->causedBy($updatedBy)
                ->performedOn($appointment)
                ->withProperties([
                    'original' => $originalData,
                    'changes' => $appointment->getChanges(),
                ])
                ->log('appointment_updated');

            DB::commit();
            
            // ✅ REMOVED: 'timeSlot' from fresh() clause
            return $appointment->fresh(['patient', 'doctor']);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update appointment: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'data' => $data,
                'updated_by' => $updatedBy->id,
            ]);
            throw $e;
        }
    }

    /**
     * Cancel appointment
     */
    public function cancelAppointment(Appointment $appointment, string $reason, User $cancelledBy): bool
    {
        DB::beginTransaction();
        
        try {
            if (!$appointment->canBeCancelled()) {
                throw new Exception('This appointment cannot be cancelled');
            }

            $success = $appointment->cancel($reason, $cancelledBy);
            
            if ($success) {
                // Log the activity
                activity()
                    ->causedBy($cancelledBy)
                    ->performedOn($appointment)
                    ->withProperties([
                        'cancellation_reason' => $reason,
                        'cancelled_by_role' => $cancelledBy->roles->first()?->code ?? 'unknown',
                    ])
                    ->log('appointment_cancelled');

                DB::commit();
                return true;
            }
            
            DB::rollBack();
            return false;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel appointment: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'reason' => $reason,
                'cancelled_by' => $cancelledBy->id,
            ]);
            throw $e;
        }
    }

    /**
     * Confirm appointment
     */
    public function confirmAppointment(Appointment $appointment, User $confirmedBy): bool
    {
        DB::beginTransaction();
        
        try {
            $success = $appointment->confirm($confirmedBy);
            
            if ($success) {
                // Log the activity
                activity()
                    ->causedBy($confirmedBy)
                    ->performedOn($appointment)
                    ->log('appointment_confirmed');

                DB::commit();
                return true;
            }
            
            DB::rollBack();
            return false;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to confirm appointment: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'confirmed_by' => $confirmedBy->id,
            ]);
            throw $e;
        }
    }

    /**
     * Complete appointment
     */
    public function completeAppointment(Appointment $appointment, User $completedBy, ?string $notes = null): bool
    {
        DB::beginTransaction();
        
        try {
            $success = $appointment->complete($completedBy, $notes);
            
            if ($success) {
                // Log the activity
                activity()
                    ->causedBy($completedBy)
                    ->performedOn($appointment)
                    ->withProperties([
                        'completion_notes' => $notes,
                    ])
                    ->log('appointment_completed');

                DB::commit();
                return true;
            }
            
            DB::rollBack();
            return false;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete appointment: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'completed_by' => $completedBy->id,
            ]);
            throw $e;
        }
    }

    /**
     * Get available time slots for a doctor on a specific date (simplified version)
     */
    public function getAvailableSlots(int $doctorId, Carbon $date): array
    {
        $doctor = User::with('doctor')->find($doctorId);
        
        if (!$doctor || !$doctor->isDoctor()) {
            throw new Exception('Invalid doctor ID');
        }

        // ✅ SIMPLIFIED: Use doctor's working hours instead of TimeSlot table
        $workingHours = $doctor->doctor->working_hours ?? [];
        $dayName = strtolower($date->format('l'));
        
        if (!isset($workingHours[$dayName]) || $workingHours[$dayName] === null) {
            return [];
        }

        $daySchedule = $workingHours[$dayName];
        $startTime = $daySchedule[0]; // "09:00"
        $endTime = $daySchedule[1];   // "17:00"

        // Get blocked time slots
        $blockedSlots = BlockedTimeSlot::where('doctor_user_id', $doctorId)
            ->whereDate('start_datetime', $date->format('Y-m-d'))
            ->get();

        // Get existing appointments
        $existingAppointments = Appointment::where('doctor_user_id', $doctorId)
            ->whereDate('appointment_datetime_start', $date->format('Y-m-d'))
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->get();

        // Generate available slots
        $availableSlots = [];
        $slotDuration = 30; // minutes
        $currentTime = Carbon::parse($date->format('Y-m-d') . ' ' . $startTime);
        $endTimeCarbon = Carbon::parse($date->format('Y-m-d') . ' ' . $endTime);

        while ($currentTime < $endTimeCarbon) {
            $slotEnd = $currentTime->copy()->addMinutes($slotDuration);

            // Check for conflicts
            $isBooked = $existingAppointments->contains(function ($appointment) use ($currentTime, $slotEnd) {
                return $appointment->appointment_datetime_start < $slotEnd && 
                       $appointment->appointment_datetime_end > $currentTime;
            });

            $isBlocked = $blockedSlots->contains(function ($blocked) use ($currentTime, $slotEnd) {
                return $blocked->start_datetime < $slotEnd && 
                       $blocked->end_datetime > $currentTime;
            });

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

        return $availableSlots;
    }

    /**
     * Reschedule appointment (Admin/Receptionist)
     */
    public function rescheduleAppointment(User $user, Appointment $appointment, array $rescheduleData): Appointment
    {
        // Check if new time slot is available
        $conflictData = [
            'doctor_user_id' => $appointment->doctor_user_id,
            'appointment_datetime_start' => $rescheduleData['new_datetime_start'],
            'appointment_datetime_end' => $rescheduleData['new_datetime_end']
        ];
        
        $this->checkForConflicts($conflictData, $appointment->id);

        // Update appointment
        $appointment->update([
            'appointment_datetime_start' => $rescheduleData['new_datetime_start'],
            'appointment_datetime_end' => $rescheduleData['new_datetime_end'],
            'notes_by_staff' => $rescheduleData['notes_by_staff'] ?? $appointment->notes_by_staff,
            'status' => Appointment::STATUS_RESCHEDULED,
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($appointment)
            ->withProperties([
                'old_datetime_start' => $appointment->getOriginal('appointment_datetime_start'),
                'old_datetime_end' => $appointment->getOriginal('appointment_datetime_end'),
                'new_datetime_start' => $rescheduleData['new_datetime_start'],
                'new_datetime_end' => $rescheduleData['new_datetime_end'],
            ])
            ->log('appointment_rescheduled_by_staff');

        return $appointment->fresh(['patient', 'doctor']);
    }

    /**
     * Private helper methods
     */
    private function validateAppointmentData(array $data, ?int $excludeAppointmentId = null, ?Appointment $existingAppointment = null): void
    {
        // For updates, use existing appointment's IDs if not provided in request
        $patientId = $data['patient_user_id'] ?? $data['patient_id'] ?? 
                    ($existingAppointment ? $existingAppointment->patient_user_id : null);
        $doctorId = $data['doctor_user_id'] ?? $data['doctor_id'] ?? 
                   ($existingAppointment ? $existingAppointment->doctor_user_id : null);

        if (empty($patientId)) {
            throw new Exception('Patient ID is required');
        }

        if (empty($doctorId)) {
            throw new Exception('Doctor ID is required');
        }

        // Only validate appointment time if it's being changed
        if (isset($data['appointment_datetime_start']) && empty($data['appointment_datetime_start'])) {
            throw new Exception('Appointment start time is required');
        }

        // Validate patient exists (only if we have a patient ID to check)
        if ($patientId) {
            $patient = User::find($patientId);
            if (!$patient || !$patient->isPatient()) {
                throw new Exception('Invalid patient ID');
            }
        }

        // Validate doctor exists (only if we have a doctor ID to check)
        if ($doctorId) {
            $doctor = User::find($doctorId);
            if (!$doctor || !$doctor->isDoctor()) {
                throw new Exception('Invalid doctor ID');
            }
        }

        // Validate appointment time is in the future (only if time is being updated)
        if (isset($data['appointment_datetime_start'])) {
            $appointmentTime = Carbon::parse($data['appointment_datetime_start']);
            // For updates, allow appointments to be scheduled for current time or future
            // (in case we're confirming an appointment that's about to start)
            if ($appointmentTime < now()->subMinutes(5)) {
                throw new Exception('Appointment time must not be in the past');
            }
        }
    }

    public function checkForConflicts(array $data, ?int $excludeAppointmentId = null): void
    {
        $doctorId = $data['doctor_user_id'] ?? $data['doctor_id'];
        $startTime = Carbon::parse($data['appointment_datetime_start']);
        $endTime = isset($data['appointment_datetime_end']) 
            ? Carbon::parse($data['appointment_datetime_end'])
            : $startTime->copy()->addMinutes(30);

        // Check for doctor conflicts
        $doctorConflict = Appointment::where('doctor_user_id', $doctorId)
            ->where('appointment_datetime_start', '<', $endTime)
            ->where('appointment_datetime_end', '>', $startTime)
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED_BY_PATIENT,
                Appointment::STATUS_CANCELLED_BY_CLINIC
            ])
            ->when($excludeAppointmentId, function ($query) use ($excludeAppointmentId) {
                $query->where('id', '!=', $excludeAppointmentId);
            })
            ->exists();

        if ($doctorConflict) {
            throw new Exception('Doctor is not available at this time');
        }

        // Check for blocked time slots
        $blockedTime = BlockedTimeSlot::where('doctor_user_id', $doctorId)
            ->where('start_datetime', '<', $endTime)
            ->where('end_datetime', '>', $startTime)
            ->exists();

        if ($blockedTime) {
            throw new Exception('Doctor has blocked this time slot');
        }
    }

    private function hasTimeChanged(Appointment $appointment, array $data): bool
    {
        if (empty($data['appointment_datetime_start'])) {
            return false;
        }

        return $appointment->appointment_datetime_start->format('Y-m-d H:i:s') !== 
               Carbon::parse($data['appointment_datetime_start'])->format('Y-m-d H:i:s');
    }
}