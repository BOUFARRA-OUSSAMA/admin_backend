<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'email_enabled',
        'push_enabled',
        'sms_enabled',
        'first_reminder_hours',
        'second_reminder_hours',
        'reminder_24h_enabled',
        'reminder_2h_enabled',
        'preferred_channels',
        'timezone',
        'custom_settings',
        'is_active',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'reminder_24h_enabled' => 'boolean',
        'reminder_2h_enabled' => 'boolean',
        'preferred_channels' => 'array',
        'custom_settings' => 'array',
        'is_active' => 'boolean',
        'first_reminder_hours' => 'integer',
        'second_reminder_hours' => 'integer',
    ];

    /**
     * Get the user that owns the reminder setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default settings for a user type
     */
    public static function getDefaults(string $userType = 'patient'): array
    {
        return [
            'user_type' => $userType,
            'email_enabled' => true,
            'push_enabled' => true,
            'sms_enabled' => false,
            'first_reminder_hours' => 24,
            'second_reminder_hours' => 2,
            'reminder_24h_enabled' => true,
            'reminder_2h_enabled' => true,
            'preferred_channels' => ['email', 'push'],
            'timezone' => 'UTC',
            'is_active' => true,
        ];
    }

    /**
     * Get or create settings for a user
     */
    public static function getOrCreateForUser(int $userId, string $userType = 'patient'): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'user_type' => $userType],
            self::getDefaults($userType)
        );
    }

    /**
     * Check if a specific reminder type is enabled
     */
    public function isReminderEnabled(string $type): bool
    {
        return match($type) {
            '24h' => $this->reminder_24h_enabled && $this->is_active,
            '2h' => $this->reminder_2h_enabled && $this->is_active,
            default => false,
        };
    }

    /**
     * Get enabled channels for this user
     */
    public function getEnabledChannels(): array
    {
        $channels = [];
        
        if ($this->email_enabled) $channels[] = 'email';
        if ($this->push_enabled) $channels[] = 'push';
        if ($this->sms_enabled) $channels[] = 'sms';
        
        return $channels;
    }
}
