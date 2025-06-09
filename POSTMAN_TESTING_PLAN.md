# Postman Testing Plan - Reminder System API

## Setup
1. **Base URL**: `http://localhost:8000`
2. **Environment Variables**:
   - `baseUrl`: `http://localhost:8000`
   - `patientToken`: (set after patient login)
   - `adminToken`: (set after admin login)

## Test Sequence

### 1. Authentication

#### Login Patient
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/auth/login`
- **Body** (JSON):
```json
{
  "email": "patient@example.com",
  "password": "password"
}
```
- **Expect**: 200, token in response
- **Action**: Save token to `patientToken` variable

#### Login Admin
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/auth/login`
- **Body** (JSON):
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```
- **Expect**: 200, token in response
- **Action**: Save token to `adminToken` variable

### 2. Patient Endpoints

#### Get Reminder Settings
- **Method**: `GET`
- **URL**: `{{baseUrl}}/api/reminders/settings`
- **Headers**: `Authorization: Bearer {{patientToken}}`
- **Expect**: 200, user settings object

#### Update Reminder Settings
- **Method**: `PUT`
- **URL**: `{{baseUrl}}/api/reminders/settings`
- **Headers**: `Authorization: Bearer {{patientToken}}`
- **Body** (JSON):
```json
{
  "email_enabled": true,
  "sms_enabled": false,
  "push_enabled": true,
  "in_app_enabled": true,
  "default_reminder_times": [60, 1440],
  "timezone": "America/New_York"
}
```
- **Expect**: 200, updated settings

#### Get Upcoming Reminders
- **Method**: `GET`
- **URL**: `{{baseUrl}}/api/reminders/upcoming`
- **Headers**: `Authorization: Bearer {{patientToken}}`
- **Expect**: 200, array of upcoming reminders

#### Get Appointment Reminders
- **Method**: `GET`
- **URL**: `{{baseUrl}}/api/appointments/1/reminders`
- **Headers**: `Authorization: Bearer {{patientToken}}`
- **Expect**: 200, appointment reminder details

#### Acknowledge Reminder
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/appointments/1/reminders/1/acknowledge`
- **Headers**: `Authorization: Bearer {{patientToken}}`
- **Body** (JSON):
```json
{
  "acknowledged_at": "2025-06-07T12:00:00Z"
}
```
- **Expect**: 200, acknowledgment confirmation

#### Opt-Out from Reminders
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/reminders/opt-out`
- **Headers**: `Authorization: Bearer {{patientToken}}`
- **Body** (JSON):
```json
{
  "channels": ["sms", "push"],
  "reason": "I prefer email only",
  "temporary": false
}
```
- **Expect**: 200, opt-out confirmation

### 3. Admin/Staff Endpoints

#### Schedule Reminders
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/reminders/schedule`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "appointment_id": 48,
  "channels": ["email", "sms"],
  "reminder_times": [60, 1440],
  "priority": "normal"
}
```
- **Expect**: 200, scheduling confirmation

#### Cancel Reminders
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/reminders/cancel`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "appointment_id": 1,
  "reason": "Appointment cancelled"
}
```
- **Expect**: 200, cancellation confirmation

#### Send Test Reminder
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/reminders/test`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "appointment_id": 1,
  "channel": "email",
  "message": "Test reminder message"
}
```
- **Expect**: 200, test sent confirmation

#### Get Reminder Logs
- **Method**: `GET`
- **URL**: `{{baseUrl}}/api/reminders/logs?start_date=2025-06-01&end_date=2025-06-07&status=delivered`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Expect**: 200, filtered logs array

#### Get Analytics
- **Method**: `GET`
- **URL**: `{{baseUrl}}/api/reminders/analytics?start_date=2025-06-01&end_date=2025-06-07`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Expect**: 200, analytics data

#### Bulk Operations
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/reminders/bulk`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "operation": "schedule",
  "appointment_ids": [1, 2, 3],
  "options": {
    "channels": ["email", "sms"],
    "custom_times": [60, 1440],
    "priority": "normal"
  }
}
```
- **Expect**: 200, bulk operation results

#### Schedule Custom Reminder
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/appointments/1/reminders/custom`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "channel": "email",
  "reminder_time": 120,
  "custom_message": "Special instructions for your appointment",
  "priority": "high"
}
```
- **Expect**: 200, custom reminder scheduled

#### Cancel Specific Reminder
- **Method**: `DELETE`
- **URL**: `{{baseUrl}}/api/appointments/1/reminders/5`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "reason": "Patient request"
}
```
- **Expect**: 200, reminder cancelled

#### Reschedule Reminder
- **Method**: `PUT`
- **URL**: `{{baseUrl}}/api/appointments/1/reminders/5/reschedule`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "new_reminder_time": 90,
  "reason": "Appointment time changed"
}
```
- **Expect**: 200, reminder rescheduled

#### Test Appointment Reminder
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/appointments/1/reminders/test`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "channel": "sms",
  "message": "Test SMS reminder"
}
```
- **Expect**: 200, test reminder sent

#### Get Reminder Status
- **Method**: `GET`
- **URL**: `{{baseUrl}}/api/appointments/1/reminders/status`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Expect**: 200, delivery status details

#### Update Appointment Preferences
- **Method**: `PUT`
- **URL**: `{{baseUrl}}/api/appointments/1/reminders/preferences`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "channels": ["email", "in_app"],
  "reminder_times": [30, 60],
  "custom_message": "Updated reminder preferences"
}
```
- **Expect**: 200, preferences updated

### 4. Authorization Tests

#### Patient Access Admin Endpoint (Should Fail)
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/reminders/schedule`
- **Headers**: `Authorization: Bearer {{patientToken}}`
- **Body** (JSON):
```json
{
  "appointment_id": 1,
  "channels": ["email"]
}
```
- **Expect**: 403, access denied

#### Unauthorized Request (Should Fail)
- **Method**: `GET`
- **URL**: `{{baseUrl}}/api/reminders/settings`
- **Headers**: None
- **Expect**: 401, unauthorized

### 5. Validation Tests

#### Invalid Reminder Time
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/reminders/schedule`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "appointment_id": 1,
  "channels": ["email"],
  "reminder_times": [1, 15000]
}
```
- **Expect**: 422, validation errors

#### Invalid Channel
- **Method**: `POST`
- **URL**: `{{baseUrl}}/api/reminders/schedule`
- **Headers**: `Authorization: Bearer {{adminToken}}`
- **Body** (JSON):
```json
{
  "appointment_id": 1,
  "channels": ["invalid_channel"],
  "reminder_times": [60]
}
```
- **Expect**: 422, validation errors

#### Missing Required Fields
- **Method**: `PUT`
- **URL**: `{{baseUrl}}/api/reminders/settings`
- **Headers**: `Authorization: Bearer {{patientToken}}`
- **Body** (JSON):
```json
{
  "email_enabled": true
}
```
- **Expect**: 422, validation errors

## Test Results Check
- All 200 responses should have `"success": true`
- All error responses should have `"success": false`
- Check response structure matches documentation
- Verify data persistence in database after each operation
