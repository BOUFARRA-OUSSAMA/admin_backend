<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\ReminderSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;

class ReminderApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $patient;
    protected User $admin;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'patient']);
        Role::create(['name' => 'admin']);

        // Create users
        $this->patient = User::factory()->create();
        $this->patient->assignRole('patient');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create an appointment
        $this->appointment = Appointment::factory()->create([
            'patient_user_id' => $this->patient->id,
            'appointment_datetime_start' => now()->addDays(1)
        ]);
    }

    public function test_can_get_reminder_settings()
    {
        $response = $this->actingAs($this->patient, 'api')
            ->getJson('/api/reminders/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'email_enabled',
                    'sms_enabled',
                    'push_enabled',
                    'in_app_enabled'
                ]
            ]);
    }

    public function test_can_update_reminder_settings()
    {
        $settings = [
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true,
            'in_app_enabled' => true,
            'reminder_times' => [60, 1440] // 1 hour and 1 day before
        ];

        $response = $this->actingAs($this->patient, 'api')
            ->putJson('/api/reminders/settings', $settings);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reminder settings updated successfully'
            ]);
    }

    public function test_admin_can_schedule_reminders()
    {
        $data = [
            'appointment_id' => $this->appointment->id,
            'channels' => ['email', 'sms'],
            'reminder_times' => [60, 1440]
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/reminders/schedule', $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    public function test_can_get_appointment_reminders()
    {
        $response = $this->actingAs($this->patient, 'api')
            ->getJson("/api/appointments/{$this->appointment->id}/reminders");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);
    }

    public function test_unauthorized_access_returns_401()
    {
        $response = $this->getJson('/api/reminders/settings');
        $response->assertStatus(401);
    }

    public function test_patient_cannot_access_admin_endpoints()
    {
        $data = [
            'appointment_id' => $this->appointment->id,
            'channels' => ['email']
        ];

        $response = $this->actingAs($this->patient, 'api')
            ->postJson('/api/reminders/schedule', $data);

        $response->assertStatus(403);
    }
}
