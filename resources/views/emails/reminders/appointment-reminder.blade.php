<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASIO - Appointment Reminder</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background-color: #f5f7fa;
            padding: 20px;
        }
        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            text-align: center;
            position: relative;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)" /></svg>');
            opacity: 0.3;
        }
        .logo-container {
            position: relative;
            z-index: 1;
        }
        .logo {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 3px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            margin-bottom: 8px;
        }
        .logo-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .reminder-badge {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            color: white;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
            border: 1px solid rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .content {
            background-color: #ffffff;
            padding: 40px;
        }
        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 16px;
        }
        .intro-text {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .appointment-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 16px;
            padding: 32px;
            margin: 32px 0;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        .appointment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .datetime-section {
            text-align: center;
            margin-bottom: 24px;
            padding: 20px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        .appointment-date {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 4px;
        }
        .appointment-time {
            font-size: 24px;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .time-until {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 24px;
        }
        .detail-item {
            background: rgba(255,255,255,0.8);
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .detail-label {
            font-size: 12px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
        }
        .action-section {
            margin: 40px 0;
            text-align: center;
        }
        .action-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 24px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        .btn-secondary {
            background: #ffffff;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }
        .btn-secondary:hover {
            border-color: #cbd5e0;
            background: #f7fafc;
        }
        .important-notes {
            background: linear-gradient(135deg, #fef5e7 0%, #fed7aa 100%);
            border-radius: 12px;
            padding: 24px;
            margin: 32px 0;
            border-left: 4px solid #f59e0b;
        }
        .important-notes h4 {
            color: #92400e;
            font-weight: 700;
            margin-bottom: 16px;
            font-size: 16px;
        }
        .notes-list {
            list-style: none;
            padding: 0;
        }
        .notes-list li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(245, 158, 11, 0.2);
            color: #78350f;
            font-weight: 500;
            position: relative;
            padding-left: 24px;
        }
        .notes-list li:last-child {
            border-bottom: none;
        }
        .notes-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #f59e0b;
            font-weight: bold;
        }
        .clinic-info {
            background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%);
            border-radius: 12px;
            padding: 24px;
            margin: 32px 0;
            border-left: 4px solid #0d9488;
        }
        .clinic-info h4 {
            color: #0f766e;
            font-weight: 700;
            margin-bottom: 16px;
            font-size: 16px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            color: #134e4a;
            font-weight: 500;
        }
        .contact-icon {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }
        .footer {
            background: #f7fafc;
            padding: 32px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer-text {
            font-size: 14px;
            color: #718096;
            margin-bottom: 8px;
        }
        .footer-brand {
            font-weight: 700;
            color: #667eea;
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 32px 0;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 24px;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .btn {
                width: 100%;
                max-width: 280px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo-container">
                <div class="logo">ASIO</div>
                <div class="logo-subtitle">Advanced Healthcare Platform</div>
                <div class="reminder-badge">
                    @if($reminderType === '24h')
                        24 Hour Reminder
                    @elseif($reminderType === '2h')
                        2 Hour Reminder
                    @elseif($reminderType === '1h')
                        1 Hour Reminder
                    @else
                        Appointment Reminder
                    @endif
                </div>
            </div>
        </div>

        <div class="content">
            <div class="greeting">
                Hello {{ $user->name ?? $appointment->patient->name ?? 'Patient' }}! 👋
            </div>
            
            <div class="intro-text">
                We hope this message finds you well. This is a friendly reminder about your upcoming medical appointment with our healthcare team.
            </div>

            <div class="appointment-card">
                <div class="datetime-section">
                    <div class="appointment-date">{{ $appointment->appointment_datetime_start->format('l, F j, Y') }}</div>
                    <div class="appointment-time">{{ $appointment->appointment_datetime_start->format('g:i A') }}</div>
                    <div class="time-until">{{ $time_until ?? $appointment->appointment_datetime_start->diffForHumans() }}</div>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Healthcare Provider</div>
                        <div class="detail-value">
                            @if($appointment->doctor)
                               {{ $appointment->doctor->name }}
                            @else
                                Will be assigned
                            @endif
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Appointment Type</div>
                        <div class="detail-value">{{ ucfirst($appointment->type ?? 'General Consultation') }}</div>
                    </div>
                    
                    @if($appointment->reason_for_visit)
                    <div class="detail-item">
                        <div class="detail-label">Purpose of Visit</div>
                        <div class="detail-value">{{ $appointment->reason_for_visit }}</div>
                    </div>
                    @endif
                    
                    <div class="detail-item">
                        <div class="detail-label">Duration</div>
                        <div class="detail-value">
                            {{ $appointment->appointment_datetime_start->diffInMinutes($appointment->appointment_datetime_end ?? $appointment->appointment_datetime_start->addMinutes(30)) }} minutes
                        </div>
                    </div>
                </div>
            </div>

            @if($custom_message ?? false)
            <div class="important-notes">
                <h4>📋 Special Instructions</h4>
                <p style="color: #78350f; font-weight: 500; margin: 0;">{{ $custom_message }}</p>
            </div>
            @endif

            <div class="action-section">
                <div class="action-buttons">
                    @if($reschedule_link ?? false)
                    <a href="{{ $reschedule_link }}" class="btn btn-primary">Reschedule Appointment</a>
                    @endif
                    
                    @if($cancellation_link ?? false)
                    <a href="{{ $cancellation_link }}" class="btn btn-secondary">Cancel Appointment</a>
                    @endif
                </div>
            </div>

            <div class="divider"></div>

            <div class="important-notes">
                <h4>📝 Important Reminders</h4>
                <ul class="notes-list">
                    <li>Please arrive 15 minutes early for registration and check-in</li>
                    <li>Bring a valid government-issued photo ID</li>
                    <li>Bring your insurance card and any required copayment</li>
                    <li>Bring a current list of all medications you're taking</li>
                    @if($appointment->type === 'follow_up')
                    <li>Bring any test results, lab work, or imaging from other providers</li>
                    @endif
                    <li>Wear comfortable, loose-fitting clothing for your examination</li>
                </ul>
            </div>

            <div class="clinic-info">
                <h4>🏥 Contact Information</h4>
                <div class="contact-item">
                    <span class="contact-icon">📞</span>
                    <span>{{ $clinic_info['phone'] ?? '(555) 123-4567' }}</span>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">✉️</span>
                    <span>{{ $clinic_info['email'] ?? 'support@asio.com' }}</span>
                </div>
                @if($clinic_info['address'] ?? false)
                <div class="contact-item">
                    <span class="contact-icon">📍</span>
                    <span>{{ $clinic_info['address'] }}</span>
                </div>
                @endif
                <div class="contact-item">
                    <span class="contact-icon">🌐</span>
                    <span>{{ $clinic_info['website'] ?? config('app.url') }}</span>
                </div>
            </div>

            <div style="background: #f0f4f8; padding: 20px; border-radius: 8px; text-align: center; color: #4a5568; font-size: 14px;">
                <strong>Need to make changes?</strong><br>
                Please contact us at least 24 hours in advance to reschedule or cancel your appointment to avoid any fees.
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                This is an automated reminder from <span class="footer-brand">ASIO</span> Healthcare Platform.
            </div>
            <div class="footer-text">
                Please do not reply directly to this email.
            </div>
            <div class="footer-text">
                &copy; {{ date('Y') }} ASIO. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
