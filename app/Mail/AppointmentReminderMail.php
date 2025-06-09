<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $appointment;
    public $user;
    public $reminderType;
    public $customMessage;
    public $actionUrls;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Appointment $appointment,
        User $user,
        string $reminderType,
        ?string $customMessage = null,
        array $actionUrls = []
    ) {
        $this->appointment = $appointment;
        $this->user = $user;
        $this->reminderType = $reminderType;
        $this->customMessage = $customMessage;
        $this->actionUrls = $actionUrls;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->reminderType) {
            '24h' => 'Appointment Reminder - Tomorrow at ' . $this->appointment->appointment_datetime_start->format('g:i A'),
            '2h' => 'Appointment Reminder - In 2 Hours',
            '1h' => 'Appointment Reminder - In 1 Hour',
            default => 'Appointment Reminder - ' . $this->appointment->appointment_datetime_start->format('M j, Y')
        };

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address', 'noreply@' . config('app.domain', 'healthcare.com')),
            replyTo: config('app.email', 'contact@healthcare.com'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlView: 'emails.reminders.appointment-reminder',
            textView: 'emails.reminders.appointment-reminder-text',
            with: [
                'appointment' => $this->appointment,
                'user' => $this->user,
                'reminderType' => $this->reminderType,
                'customMessage' => $this->customMessage,
                'confirmationUrl' => $this->actionUrls['confirm'] ?? null,
                'rescheduleUrl' => $this->actionUrls['reschedule'] ?? null,
                'cancelUrl' => $this->actionUrls['cancel'] ?? null,
                'unsubscribeUrl' => $this->actionUrls['unsubscribe'] ?? null,
                'clinicPhone' => config('app.phone'),
                'clinicEmail' => config('app.email'),
                'clinicAddress' => config('app.address'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
