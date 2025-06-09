<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Reminder</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .reminder-badge {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .appointment-details {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #333;
        }
        .datetime-highlight {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #007bff;
        }
        .datetime-highlight .date {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }
        .datetime-highlight .time {
            font-size: 18px;
            color: #333;
            margin-top: 5px;
        }
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .contact-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
            }
            .btn {
                display: block;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name', 'Healthcare') }}</div>
            <div class="reminder-badge">
                {{ $reminderType === '24h' ? '24 Hour Reminder' : ($reminderType === '2h' ? '2 Hour Reminder' : 'Appointment Reminder') }}
            </div>
        </div>

        <h2>Hello {{ $user->name ?? $appointment->patient->name ?? 'Patient' }},</h2>
        
        <p>This is a friendly reminder about your upcoming medical appointment.</p>

        <div class="datetime-highlight">
            <div class="date">{{ $appointment->appointment_datetime_start->format('l, F j, Y') }}</div>
            <div class="time">{{ $appointment->appointment_datetime_start->format('g:i A') }}</div>
        </div>

        <div class="appointment-details">
            <h3 style="margin-top: 0; color: #007bff;">Appointment Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">Doctor:</span>
                <span class="detail-value">
                    @if($appointment->doctor)
                        Dr. {{ $appointment->doctor->name }}
                    @else
                        To be assigned
                    @endif
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Appointment Type:</span>
                <span class="detail-value">{{ ucfirst($appointment->type ?? 'General Consultation') }}</span>
            </div>
            
            @if($appointment->reason_for_visit)
            <div class="detail-row">
                <span class="detail-label">Reason for Visit:</span>
                <span class="detail-value">{{ $appointment->reason_for_visit }}</span>
            </div>
            @endif
            
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">{{ ucfirst($appointment->status) }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Duration:</span>
                <span class="detail-value">
                    {{ $appointment->appointment_datetime_start->diffInMinutes($appointment->appointment_datetime_end) }} minutes
                </span>
            </div>
        </div>

        @if($customMessage ?? false)
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <strong>Special Instructions:</strong><br>
            {{ $customMessage }}
        </div>
        @endif

        <div class="action-buttons">
            @if($confirmationUrl ?? false)
            <a href="{{ $confirmationUrl }}" class="btn btn-primary">Confirm Appointment</a>
            @endif
            
            @if($rescheduleUrl ?? false)
            <a href="{{ $rescheduleUrl }}" class="btn btn-secondary">Reschedule</a>
            @endif
            
            @if($cancelUrl ?? false)
            <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancel Appointment</a>
            @endif
        </div>

        <div class="contact-info">
            <h4 style="margin-top: 0;">Clinic Information</h4>
            <p style="margin: 5px 0;"><strong>Phone:</strong> {{ $clinicPhone ?? config('app.phone', '(555) 123-4567') }}</p>
            <p style="margin: 5px 0;"><strong>Email:</strong> {{ $clinicEmail ?? config('app.email', 'contact@healthcare.com') }}</p>
            @if($clinicAddress ?? false)
            <p style="margin: 5px 0;"><strong>Address:</strong> {{ $clinicAddress }}</p>
            @endif
        </div>

        <p><strong>Important Reminders:</strong></p>
        <ul>
            <li>Please arrive 15 minutes early for check-in</li>
            <li>Bring a valid ID and insurance card</li>
            <li>Bring a list of current medications</li>
            @if($appointment->type === 'follow_up')
            <li>Bring any test results or reports from previous visits</li>
            @endif
        </ul>

        <p>If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance.</p>

        <div class="footer">
            <p>This is an automated reminder. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            @if($unsubscribeUrl ?? false)
            <p><a href="{{ $unsubscribeUrl }}" style="color: #666;">Unsubscribe from reminders</a></p>
            @endif
        </div>
    </div>
</body>
</html>
