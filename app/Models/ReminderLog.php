<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'user_id',
        'reminder_type',
        'channel',
        'trigger_type',
        'scheduled_for',
        'sent_at',
        'delivery_status',
        'subject',
        'message_content',
        'job_id',
        'metadata',
        'error_message',
        'retry_count',
        'last_retry_at',
        'opened_at',
        'clicked_at',
        'tracking_token',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'metadata' => 'array',
        'retry_count' => 'integer',
    ];

    /**
     * Get the appointment this reminder belongs to.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the user this reminder was sent to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the scheduled reminder job.
     */
    public function scheduledJob(): BelongsTo
    {
        return $this->belongsTo(ScheduledReminderJob::class, 'job_id', 'job_id');
    }

    /**
     * Scope for sent reminders
     */
    public function scopeSent($query)
    {
        return $query->whereIn('delivery_status', ['sent', 'delivered']);
    }

    /**
     * Scope for failed reminders
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('delivery_status', ['failed', 'bounced']);
    }

    /**
     * Scope for pending reminders
     */
    public function scopePending($query)
    {
        return $query->where('delivery_status', 'pending');
    }

    /**
     * Mark reminder as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'sent_at' => now(),
            'delivery_status' => 'sent',
        ]);
    }

    /**
     * Mark reminder as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'delivery_status' => 'delivered',
        ]);
    }

    /**
     * Mark reminder as failed
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'delivery_status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Mark reminder as opened (email tracking)
     */
    public function markAsOpened(): void
    {
        if (!$this->opened_at) {
            $this->update(['opened_at' => now()]);
        }
    }

    /**
     * Mark reminder as clicked (link tracking)
     */
    public function markAsClicked(): void
    {
        if (!$this->clicked_at) {
            $this->update(['clicked_at' => now()]);
        }
    }

    /**
     * Generate tracking token
     */
    public function generateTrackingToken(): string
    {
        $token = str()->random(32);
        $this->update(['tracking_token' => $token]);
        return $token;
    }

    /**
     * Check if reminder can be retried
     */
    public function canRetry(): bool
    {
        return $this->retry_count < 3 && 
               in_array($this->delivery_status, ['pending', 'failed']);
    }

    /**
     * Get delivery rate for a date range
     */
    public static function getDeliveryRate(Carbon $from, Carbon $to): float
    {
        $total = self::whereBetween('created_at', [$from, $to])->count();
        if ($total === 0) return 0;

        $delivered = self::whereBetween('created_at', [$from, $to])
            ->whereIn('delivery_status', ['sent', 'delivered'])
            ->count();

        return round(($delivered / $total) * 100, 2);
    }
}
