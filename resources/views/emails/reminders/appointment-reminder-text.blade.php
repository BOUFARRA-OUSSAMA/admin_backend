{{ config('app.name', 'Healthcare') }} - Appointment Reminder

Hello {{ $user->name ?? $appointment->patient->name ?? 'Patient' }},

This is a {{ $reminderType === '24h' ? '24 hour' : ($reminderType === '2h' ? '2 hour' : '') }} reminder about your upcoming medical appointment.

APPOINTMENT DETAILS
===================
Date: {{ $appointment->appointment_datetime_start->format('l, F j, Y') }}
Time: {{ $appointment->appointment_datetime_start->format('g:i A') }}
@if($appointment->doctor)
Doctor: Dr. {{ $appointment->doctor->name }}
@else
Doctor: To be assigned
@endif
Type: {{ ucfirst($appointment->type ?? 'General Consultation') }}
@if($appointment->reason_for_visit)
Reason: {{ $appointment->reason_for_visit }}
@endif
Status: {{ ucfirst($appointment->status) }}
Duration: {{ $appointment->appointment_datetime_start->diffInMinutes($appointment->appointment_datetime_end) }} minutes

@if($customMessage ?? false)
SPECIAL INSTRUCTIONS
===================
{{ $customMessage }}

@endif
CLINIC INFORMATION
==================
Phone: {{ $clinicPhone ?? config('app.phone', '(555) 123-4567') }}
Email: {{ $clinicEmail ?? config('app.email', 'contact@healthcare.com') }}
@if($clinicAddress ?? false)
Address: {{ $clinicAddress }}
@endif

IMPORTANT REMINDERS
==================
- Please arrive 15 minutes early for check-in
- Bring a valid ID and insurance card
- Bring a list of current medications
@if($appointment->type === 'follow_up')
- Bring any test results or reports from previous visits
@endif

If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance.

@if($confirmationUrl ?? false)
Confirm your appointment: {{ $confirmationUrl }}
@endif
@if($rescheduleUrl ?? false)
Reschedule your appointment: {{ $rescheduleUrl }}
@endif
@if($cancelUrl ?? false)
Cancel your appointment: {{ $cancelUrl }}
@endif

This is an automated reminder. Please do not reply to this email.

© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
@if($unsubscribeUrl ?? false)

Unsubscribe from reminders: {{ $unsubscribeUrl }}
@endif
