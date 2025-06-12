<?php

namespace App\Notifications;

use App\Models\PatientFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientFileUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected PatientFile $file;

    /**
     * Create a new notification instance.
     */
    public function __construct(PatientFile $file)
    {
        $this->file = $file;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $patient = $this->file->patient;
        $patientName = $patient->personalInfo->name ?? 'Unknown Patient';
        
        return (new MailMessage)
            ->subject('New Patient File Uploaded')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new medical file has been uploaded for one of your patients.')
            ->line('**Patient:** ' . $patientName)
            ->line('**File Type:** ' . ucfirst(str_replace('_', ' ', $this->file->category)))
            ->line('**Description:** ' . $this->file->description)
            ->line('**Uploaded:** ' . $this->file->created_at->format('M j, Y g:i A'))
            ->action('View Patient Files', url('/patients/' . $patient->id . '/files'))
            ->line('Please review the uploaded file at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $patient = $this->file->patient;
        $patientName = $patient->personalInfo->name ?? 'Unknown Patient';

        return [
            'title' => 'New Patient File Uploaded',
            'message' => "New {$this->file->category} file uploaded for patient {$patientName}",
            'type' => 'file_upload',
            'patient_id' => $patient->id,
            'patient_name' => $patientName,
            'file_id' => $this->file->id,
            'file_category' => $this->file->category,
            'file_description' => $this->file->description,
            'uploaded_at' => $this->file->created_at->toISOString(),
            'action_url' => "/patients/{$patient->id}/files/{$this->file->id}"
        ];
    }
}
