<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ReminderAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'analytics_date',
        'doctor_id',
        'reminders_sent',
        'reminders_delivered',
        'reminders_failed',
        'reminders_opened',
        'reminders_clicked',
        'email_sent',
        'push_sent',
        'sms_sent',
        'in_app_sent',
        'appointments_kept',
        'appointments_cancelled',
        'appointments_no_show',
        'appointments_rescheduled',
        'delivery_rate',
        'open_rate',
        'click_rate',
        'attendance_rate',
        'avg_response_time',
        'fastest_response_time',
        'slowest_response_time',
    ];

    protected $casts = [
        'analytics_date' => 'date',
        'reminders_sent' => 'integer',
        'reminders_delivered' => 'integer',
        'reminders_failed' => 'integer',
        'reminders_opened' => 'integer',
        'reminders_clicked' => 'integer',
        'email_sent' => 'integer',
        'push_sent' => 'integer',
        'sms_sent' => 'integer',
        'in_app_sent' => 'integer',
        'appointments_kept' => 'integer',
        'appointments_cancelled' => 'integer',
        'appointments_no_show' => 'integer',
        'appointments_rescheduled' => 'integer',
        'delivery_rate' => 'decimal:2',
        'open_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
        'attendance_rate' => 'decimal:2',
        'avg_response_time' => 'integer',
        'fastest_response_time' => 'integer',
        'slowest_response_time' => 'integer',
    ];

    /**
     * Get the doctor this analytics belongs to.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Calculate and update rates
     */
    public function calculateRates(): void
    {
        // Calculate delivery rate
        $this->delivery_rate = $this->reminders_sent > 0 
            ? round(($this->reminders_delivered / $this->reminders_sent) * 100, 2)
            : 0;

        // Calculate open rate
        $this->open_rate = $this->reminders_delivered > 0 
            ? round(($this->reminders_opened / $this->reminders_delivered) * 100, 2)
            : 0;

        // Calculate click rate
        $this->click_rate = $this->reminders_opened > 0 
            ? round(($this->reminders_clicked / $this->reminders_opened) * 100, 2)
            : 0;

        // Calculate attendance rate
        $totalAppointments = $this->appointments_kept + $this->appointments_cancelled + 
                           $this->appointments_no_show + $this->appointments_rescheduled;
        
        $this->attendance_rate = $totalAppointments > 0 
            ? round(($this->appointments_kept / $totalAppointments) * 100, 2)
            : 0;

        $this->save();
    }

    /**
     * Get or create analytics for a specific date and doctor
     */
    public static function getOrCreateForDate(Carbon $date, ?int $doctorId = null): self
    {
        return self::firstOrCreate([
            'analytics_date' => $date->format('Y-m-d'),
            'doctor_id' => $doctorId,
        ]);
    }

    /**
     * Update analytics with reminder data
     */
    public function incrementReminder(string $channel, string $status): void
    {
        $this->increment('reminders_sent');

        // Increment channel-specific counter
        match($channel) {
            'email' => $this->increment('email_sent'),
            'push' => $this->increment('push_sent'),
            'sms' => $this->increment('sms_sent'),
            'in_app' => $this->increment('in_app_sent'),
            default => null,
        };

        // Increment status counter
        match($status) {
            'delivered' => $this->increment('reminders_delivered'),
            'failed' => $this->increment('reminders_failed'),
            default => null,
        };

        $this->calculateRates();
    }

    /**
     * Record appointment outcome
     */
    public function recordAppointmentOutcome(string $outcome): void
    {
        match($outcome) {
            'kept', 'completed' => $this->increment('appointments_kept'),
            'cancelled' => $this->increment('appointments_cancelled'),
            'no_show' => $this->increment('appointments_no_show'),
            'rescheduled' => $this->increment('appointments_rescheduled'),
            default => null,
        };

        $this->calculateRates();
    }

    /**
     * Get analytics summary for date range
     */
    public static function getSummary(Carbon $from, Carbon $to, ?int $doctorId = null): array
    {
        $query = self::whereBetween('analytics_date', [$from, $to]);
        
        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $data = $query->get();

        return [
            'total_reminders_sent' => $data->sum('reminders_sent'),
            'total_reminders_delivered' => $data->sum('reminders_delivered'),
            'total_reminders_failed' => $data->sum('reminders_failed'),
            'total_reminders_opened' => $data->sum('reminders_opened'),
            'total_reminders_clicked' => $data->sum('reminders_clicked'),
            'avg_delivery_rate' => $data->avg('delivery_rate'),
            'avg_open_rate' => $data->avg('open_rate'),
            'avg_click_rate' => $data->avg('click_rate'),
            'avg_attendance_rate' => $data->avg('attendance_rate'),
            'channel_breakdown' => [
                'email' => $data->sum('email_sent'),
                'push' => $data->sum('push_sent'),
                'sms' => $data->sum('sms_sent'),
                'in_app' => $data->sum('in_app_sent'),
            ],
        ];
    }
}
