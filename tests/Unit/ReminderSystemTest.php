<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\User;
use App\Models\ReminderSetting;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReminderSystemTest extends TestCase
{
    use RefreshDatabase;

    private ReminderService $reminderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reminderService = new ReminderService();
    }

    /** @test */
    public function it_can_create_default_reminder_settings()
    {
        $user = User::factory()->create();
        
        $settings = ReminderSetting::getOrCreateForUser($user->id, 'patient');
        
        $this->assertInstanceOf(ReminderSetting::class, $settings);
        $this->assertEquals($user->id, $settings->user_id);
        $this->assertEquals('patient', $settings->user_type);
        $this->assertTrue($settings->email_enabled);
        $this->assertTrue($settings->push_enabled);
        $this->assertFalse($settings->sms_enabled);
        $this->assertEquals(24, $settings->first_reminder_hours);
        $this->assertEquals(2, $settings->second_reminder_hours);
    }

    /** @test */
    public function it_can_get_enabled_channels()
    {
        $user = User::factory()->create();
        $settings = ReminderSetting::create([
            'user_id' => $user->id,
            'user_type' => 'patient',
            'email_enabled' => true,
            'push_enabled' => false,
            'sms_enabled' => true,
            'first_reminder_hours' => 24,
            'second_reminder_hours' => 2,
            'reminder_24h_enabled' => true,
            'reminder_2h_enabled' => true,
            'is_active' => true,
        ]);

        $enabledChannels = $settings->getEnabledChannels();
        
        $this->assertContains('email', $enabledChannels);
        $this->assertContains('sms', $enabledChannels);
        $this->assertNotContains('push', $enabledChannels);
    }

    /** @test */
    public function it_can_check_reminder_type_enabled()
    {
        $user = User::factory()->create();
        $settings = ReminderSetting::create([
            'user_id' => $user->id,
            'user_type' => 'patient',
            'email_enabled' => true,
            'push_enabled' => true,
            'sms_enabled' => false,
            'first_reminder_hours' => 24,
            'second_reminder_hours' => 2,
            'reminder_24h_enabled' => true,
            'reminder_2h_enabled' => false,
            'is_active' => true,
        ]);

        $this->assertTrue($settings->isReminderEnabled('24h'));
        $this->assertFalse($settings->isReminderEnabled('2h'));
        $this->assertFalse($settings->isReminderEnabled('invalid'));
    }

    /** @test */
    public function it_validates_reminder_settings_correctly()
    {
        $reminderService = new ReminderService();
        
        // Use reflection to access private method for testing
        $reflection = new \ReflectionClass($reminderService);
        $method = $reflection->getMethod('validateReminderSettings');
        $method->setAccessible(true);
        
        $settings = [
            'email_enabled' => 'true',
            'push_enabled' => 1,
            'sms_enabled' => false,
            'first_reminder_hours' => '48',
            'second_reminder_hours' => '1',
            'reminder_24h_enabled' => 'yes', // Should be converted to boolean
            'timezone' => 'UTC',
            'invalid_setting' => 'should_be_ignored'
        ];
        
        $validated = $method->invoke($reminderService, $settings);
        
        $this->assertTrue($validated['email_enabled']);
        $this->assertTrue($validated['push_enabled']);
        $this->assertFalse($validated['sms_enabled']);
        $this->assertEquals(48, $validated['first_reminder_hours']);
        $this->assertEquals(1, $validated['second_reminder_hours']);
        $this->assertTrue($validated['reminder_24h_enabled']);
        $this->assertEquals('UTC', $validated['timezone']);
        $this->assertArrayNotHasKey('invalid_setting', $validated);
    }
}
