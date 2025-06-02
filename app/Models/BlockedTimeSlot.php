<?php
// filepath: c:\Users\Sefanos\Desktop\n8n\Frontend\admin_backend\app\Models\BlockedTimeSlot.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BlockedTimeSlot extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'doctor_user_id',
        'start_datetime',
        'end_datetime',
        'reason',
        'block_type',
        'is_recurring',
        'recurring_pattern',
        'recurring_end_date',
        'created_by_user_id',
        'notes'
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'is_recurring' => 'boolean',
        'recurring_end_date' => 'date',
        'deleted_at' => 'datetime'
    ];

    // Block type constants
    const TYPE_VACATION = 'vacation';
    const TYPE_SICK_LEAVE = 'sick_leave';
    const TYPE_MEETING = 'meeting';
    const TYPE_TRAINING = 'training';
    const TYPE_PERSONAL = 'personal';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_EMERGENCY = 'emergency';

    public static function getBlockTypes(): array
    {
        return [
            self::TYPE_VACATION,
            self::TYPE_SICK_LEAVE,
            self::TYPE_MEETING,
            self::TYPE_TRAINING,
            self::TYPE_PERSONAL,
            self::TYPE_MAINTENANCE,
            self::TYPE_EMERGENCY,
        ];
    }

    // Relationships
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Scopes
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_user_id', $doctorId);
    }

    public function scopeActive($query)
    {
        return $query->where('end_datetime', '>=', now());
    }

    public function scopeForDate($query, $date)
    {
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();
        
        return $query->where(function($q) use ($startOfDay, $endOfDay) {
            $q->whereBetween('start_datetime', [$startOfDay, $endOfDay])
              ->orWhereBetween('end_datetime', [$startOfDay, $endOfDay])
              ->orWhere(function($subQ) use ($startOfDay, $endOfDay) {
                  $subQ->where('start_datetime', '<=', $startOfDay)
                       ->where('end_datetime', '>=', $endOfDay);
              });
        });
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_datetime', [$startDate, $endDate])
              ->orWhereBetween('end_datetime', [$startDate, $endDate])
              ->orWhere(function($subQ) use ($startDate, $endDate) {
                  $subQ->where('start_datetime', '<=', $startDate)
                       ->where('end_datetime', '>=', $endDate);
              });
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('block_type', $type);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    // Accessors
    public function getStartDateAttribute()
    {
        return $this->start_datetime->format('Y-m-d');
    }

    public function getStartTimeAttribute()
    {
        return $this->start_datetime->format('H:i');
    }

    public function getEndDateAttribute()
    {
        return $this->end_datetime->format('Y-m-d');
    }

    public function getEndTimeAttribute()
    {
        return $this->end_datetime->format('H:i');
    }

    public function getDurationMinutesAttribute()
    {
        return $this->start_datetime->diffInMinutes($this->end_datetime);
    }

    public function getDurationHoursAttribute()
    {
        return round($this->duration_minutes / 60, 2);
    }

    // Business Logic Methods
    public function isActive(): bool
    {
        return $this->end_datetime >= now();
    }

    public function isPast(): bool
    {
        return $this->end_datetime < now();
    }

    public function isToday(): bool
    {
        return $this->start_datetime->isToday() || $this->end_datetime->isToday();
    }

    public function conflictsWith(Carbon $startTime, Carbon $endTime): bool
    {
        return $startTime < $this->end_datetime && $endTime > $this->start_datetime;
    }

    public function overlapsWith(Carbon $startTime, Carbon $endTime): array
    {
        if (!$this->conflictsWith($startTime, $endTime)) {
            return [];
        }

        $overlapStart = $startTime->max($this->start_datetime);
        $overlapEnd = $endTime->min($this->end_datetime);

        return [
            'start' => $overlapStart,
            'end' => $overlapEnd,
            'duration_minutes' => $overlapStart->diffInMinutes($overlapEnd),
            'blocked_reason' => $this->reason,
            'block_type' => $this->block_type,
        ];
    }

    public function canBeModified(): bool
    {
        // Can't modify blocks that have already started
        return $this->start_datetime > now();
    }

    public function canBeDeleted(): bool
    {
        // Can delete if not started yet, or if it's an emergency block
        return $this->start_datetime > now() || $this->block_type === self::TYPE_EMERGENCY;
    }

    // Static helper methods
    public static function createRecurringBlock(array $data): array
    {
        $blocks = [];
        $startDate = Carbon::parse($data['start_datetime']);
        $endDate = Carbon::parse($data['recurring_end_date'] ?? $startDate->copy()->addMonths(3));
        $pattern = $data['recurring_pattern'] ?? 'weekly';

        $current = $startDate->copy();
        $duration = Carbon::parse($data['start_datetime'])->diffInMinutes(Carbon::parse($data['end_datetime']));

        while ($current <= $endDate) {
            $blockEnd = $current->copy()->addMinutes($duration);
            
            $blocks[] = static::create([
                'doctor_user_id' => $data['doctor_user_id'],
                'start_datetime' => $current->copy(),
                'end_datetime' => $blockEnd,
                'reason' => $data['reason'],
                'block_type' => $data['block_type'],
                'is_recurring' => true,
                'recurring_pattern' => $pattern,
                'recurring_end_date' => $endDate,
                'created_by_user_id' => $data['created_by_user_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Advance to next occurrence
            switch ($pattern) {
                case 'daily':
                    $current->addDay();
                    break;
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                default:
                    $current->addWeek();
            }
        }

        return $blocks;
    }

    public static function getBlocksForDoctor(int $doctorId, Carbon $date): array
    {
        return static::forDoctor($doctorId)
            ->forDate($date)
            ->active()
            ->get()
            ->map(fn($block) => [
                'id' => $block->id,
                'start_time' => $block->start_time,
                'end_time' => $block->end_time,
                'reason' => $block->reason,
                'type' => $block->block_type,
                'duration_minutes' => $block->duration_minutes,
                'is_recurring' => $block->is_recurring,
            ])
            ->toArray();
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'doctor_user_id',
                'start_datetime',
                'end_datetime',
                'reason',
                'block_type',
                'is_recurring'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // For frontend compatibility
    public function toCalendarEvent(): array
    {
        return [
            'id' => "blocked_{$this->id}",
            'title' => "🚫 {$this->reason}",
            'start' => $this->start_datetime->toISOString(),
            'end' => $this->end_datetime->toISOString(),
            'backgroundColor' => $this->getTypeColor(),
            'borderColor' => $this->getTypeColor(),
            'textColor' => '#ffffff',
            'display' => 'background',
            'extendedProps' => [
                'blockId' => $this->id,
                'blockType' => $this->block_type,
                'reason' => $this->reason,
                'doctorName' => $this->doctor->name,
                'isRecurring' => $this->is_recurring,
                'canModify' => $this->canBeModified(),
                'notes' => $this->notes,
            ]
        ];
    }

    private function getTypeColor(): string
    {
        return match($this->block_type) {
            self::TYPE_VACATION => '#17a2b8',
            self::TYPE_SICK_LEAVE => '#dc3545',
            self::TYPE_MEETING => '#6f42c1',
            self::TYPE_TRAINING => '#20c997',
            self::TYPE_PERSONAL => '#fd7e14',
            self::TYPE_MAINTENANCE => '#6c757d',
            self::TYPE_EMERGENCY => '#dc3545',
            default => '#343a40'
        };
    }
}