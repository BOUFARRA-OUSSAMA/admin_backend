<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class ScheduledReminderJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'reminder_log_id',
        'job_id',
        'reminder_type',
        'scheduled_for',
        'status',
        'attempts',
        'max_attempts',
        'last_attempted_at',
        'failed_at',
        'job_payload',
        'failure_reason',
        'is_cancelled',
        'cancelled_at',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'last_attempted_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'job_payload' => 'array',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'is_cancelled' => 'boolean',
    ];

    /**
     * Get the appointment this job belongs to.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the reminder log associated with this job.
     */
    public function reminderLog(): BelongsTo
    {
        return $this->belongsTo(ReminderLog::class);
    }

    /**
     * Scope for pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where('is_cancelled', false);
    }

    /**
     * Scope for active jobs (not cancelled, not expired)
     */
    public function scopeActive($query)
    {
        return $query->where('is_cancelled', false)
                    ->where('status', '!=', 'expired')
                    ->where('scheduled_for', '>', now());
    }

    /**
     * Scope for jobs scheduled for a specific reminder type
     */
    public function scopeForReminderType($query, string $type)
    {
        return $query->where('reminder_type', $type);
    }

    /**
     * Mark job as cancelled
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'status' => 'cancelled',
            'failure_reason' => $reason ?? 'Manually cancelled',
        ]);
    }

    /**
     * Mark job as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'last_attempted_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Mark job as sent successfully
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
        ]);
    }

    /**
     * Mark job as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
            'attempts' => $this->attempts + 1,
            'last_attempted_at' => now(),
        ]);
    }

    /**
     * Check if job can be retried
     */
    public function canRetry(): bool
    {
        return $this->attempts < $this->max_attempts && 
               !$this->is_cancelled && 
               $this->status === 'failed';
    }

    /**
     * Check if job is expired
     */
    public function isExpired(): bool
    {
        return $this->scheduled_for->isPast() && 
               in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Mark expired jobs
     */
    public static function markExpiredJobs(): int
    {
        return self::where('scheduled_for', '<', now())
            ->whereIn('status', ['pending', 'processing'])
            ->where('is_cancelled', false)
            ->update([
                'status' => 'expired',
                'failure_reason' => 'Job expired - scheduled time passed',
            ]);
    }

    /**
     * Cancel all jobs for an appointment
     */
    public static function cancelJobsForAppointment(int $appointmentId, string $reason = 'Appointment modified'): int
    {
        return self::where('appointment_id', $appointmentId)
            ->where('is_cancelled', false)
            ->whereIn('status', ['pending', 'processing'])
            ->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'status' => 'cancelled',
                'failure_reason' => $reason,
            ]);
    }
}
