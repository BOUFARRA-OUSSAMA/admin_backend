<?php

namespace App\Notifications;

use App\Models\PatientFile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientFileReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected PatientFile $file;
    protected User $doctor;
    protected ?string $review;

    public function __construct(PatientFile $file, User $doctor, ?string $review = null)
    {
        $this->file = $file;
        $this->doctor = $doctor;
        $this->review = $review;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'File Reviewed',
            'message' => "Your {$this->file->category} file has been reviewed by Dr. {$this->doctor->name}",
            'type' => 'file_review',
            'action_url' => "/patient/files/{$this->file->id}",
            'file_id' => $this->file->id,
            'doctor_name' => $this->doctor->name,
            'review' => $this->review
        ];
    }
}
