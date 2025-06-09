================================================
     ASIO - Advanced Healthcare Platform
================================================

Hello {{ $user->name ?? $appointment->patient->name ?? 'Patient' }}! 👋

We hope this message finds you well. This is a friendly reminder about your upcoming medical appointment with our healthcare team.

@if($reminderType === '24h')
⏰ 24 HOUR REMINDER
@elseif($reminderType === '2h')
⏰ 2 HOUR REMINDER  
@elseif($reminderType === '1h')
⏰ 1 HOUR REMINDER
@else
📅 APPOINTMENT REMINDER
@endif

================================================
          APPOINTMENT DETAILS
================================================

📅 Date: {{ $appointment->appointment_datetime_start->format('l, F j, Y') }}
🕐 Time: {{ $appointment->appointment_datetime_start->format('g:i A') }}
⏳ Time Until: {{ $time_until ?? $appointment->appointment_datetime_start->diffForHumans() }}

👨‍⚕️ Healthcare Provider: 
@if($appointment->doctor)
   Dr. {{ $appointment->doctor->name }}
@else
   Will be assigned
@endif

🏥 Appointment Type: {{ ucfirst($appointment->type ?? 'General Consultation') }}

@if($appointment->reason_for_visit)
📋 Purpose of Visit: {{ $appointment->reason_for_visit }}
@endif

⏱️ Duration: {{ $appointment->appointment_datetime_start->diffInMinutes($appointment->appointment_datetime_end ?? $appointment->appointment_datetime_start->addMinutes(30)) }} minutes

@if($custom_message ?? false)
================================================
          SPECIAL INSTRUCTIONS
================================================
📝 {{ $custom_message }}
@endif

================================================
          IMPORTANT REMINDERS
================================================
✅ Please arrive 15 minutes early for registration and check-in
✅ Bring a valid government-issued photo ID
✅ Bring your insurance card and any required copayment
✅ Bring a current list of all medications you're taking
@if($appointment->type === 'follow_up')
✅ Bring any test results, lab work, or imaging from other providers
@endif
✅ Wear comfortable, loose-fitting clothing for your examination

================================================
          CONTACT INFORMATION
================================================
📞 Phone: {{ $clinic_info['phone'] ?? '(555) 123-4567' }}
✉️ Email: {{ $clinic_info['email'] ?? 'support@asio.com' }}
@if($clinic_info['address'] ?? false)
📍 Address: {{ $clinic_info['address'] }}
@endif
🌐 Website: {{ $clinic_info['website'] ?? config('app.url') }}

================================================
Need to make changes?
Please contact us at least 24 hours in advance to reschedule or cancel your appointment to avoid any fees.

@if($reschedule_link ?? false)
🔗 Reschedule: {{ $reschedule_link }}
@endif
@if($cancellation_link ?? false)
🔗 Cancel: {{ $cancellation_link }}
@endif

================================================
This is an automated reminder from ASIO Healthcare Platform.
Please do not reply directly to this email.

© {{ date('Y') }} ASIO. All rights reserved.
================================================
