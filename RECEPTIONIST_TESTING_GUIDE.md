# 🧪 Receptionist Workflow Testing Guide

## 📋 Overview
This guide provides **step-by-step testing scenarios** for the Receptionist Workflow Postman collection, focusing on real-world appointment and reminder management tasks.

## 🎯 Testing Objectives
- Verify appointment CRUD operations work correctly
- Confirm automatic reminder system functionality
- Test receptionist-specific workflows
- Validate error handling and security

## 🚀 Pre-Test Setup

### 1. Environment Preparation
```bash
# Ensure Laravel backend is running
php artisan serve

# Verify database is accessible
php artisan migrate:status

# Check queue worker is running (for reminders)
php artisan queue:work --daemon
```

### 2. Required Test Data
Before testing, ensure you have:
- ✅ Valid receptionist user account
- ✅ At least 2 patient records
- ✅ At least 2 doctor records
- ✅ Proper role/permission setup

### 3. Collection Variables Setup
Update these variables in Postman:
```json
{
  "base_url": "http://localhost:8000",
  "patient_id": "3",
  "doctor_id": "4", 
  "current_date": "2025-06-15"
}
```

## 🧪 Test Scenarios

### 🔐 Scenario 1: Authentication & Authorization

#### Test 1.1: Receptionist Login
**Request:** `POST /auth/login`
```json
{
  "email": "receptionist@clinic.com",
  "password": "password"
}
```
**Expected Result:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 5,
      "email": "receptionist@clinic.com",
      "role": "receptionist"
    }
  }
}
```
**✅ Success Criteria:** 
- Status: 200
- Token auto-saved to `{{receptionist_token}}`
- User role is "receptionist" or has appointment permissions

#### Test 1.2: Verify Permissions
**Request:** `GET /auth/me`
**Headers:** `Authorization: Bearer {{receptionist_token}}`

**✅ Success Criteria:**
- Status: 200
- User has `appointments:manage` permission
- Role allows appointment and reminder operations

---

### 📅 Scenario 2: Appointment CRUD Operations

#### Test 2.1: View Today's Appointments
**Request:** `GET /appointments?today=true`
**Headers:** `Authorization: Bearer {{receptionist_token}}`

**Expected Response Structure:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "patient": { "name": "John Doe" },
      "doctor": { "name": "Dr. Smith" },
      "appointment_datetime_start": "2025-06-15 09:00:00",
      "status": "confirmed"
    }
  ],
  "pagination": { ... }
}
```

**✅ Success Criteria:**
- Status: 200
- Returns only today's appointments
- Includes patient and doctor information
- Proper pagination structure

#### Test 2.2: Create New Appointment (With Auto-Reminders)
**Request:** `POST /appointments`
```json
{
  "patient_id": 3,
  "doctor_id": 4,
  "appointment_datetime_start": "2025-06-20 09:30:00",
  "appointment_datetime_end": "2025-06-20 10:00:00",
  "type": "consultation",
  "reason": "Annual checkup",
  "staff_notes": "New patient booking"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Appointment created successfully",
  "data": {
    "id": 123,
    "patient_id": 3,
    "doctor_id": 4,
    "appointment_datetime_start": "2025-06-20 09:30:00",
    "status": "pending"
  }
}
```

**✅ Success Criteria:**
- Status: 201
- Appointment ID auto-saved to `{{appointment_id}}`
- **AUTOMATIC REMINDERS:** System logs show reminders scheduled
- Patient receives 24h and 2h reminders automatically

#### Test 2.3: Verify Auto-Generated Reminders
**Request:** `GET /appointments/{{appointment_id}}/reminders`
**Headers:** `Authorization: Bearer {{receptionist_token}}`

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "appointment_id": 123,
      "type": "email",
      "scheduled_time": "2025-06-19 09:30:00",
      "status": "scheduled",
      "message": "Reminder: You have an appointment tomorrow..."
    },
    {
      "id": 2,
      "appointment_id": 123,
      "type": "email", 
      "scheduled_time": "2025-06-20 07:30:00",
      "status": "scheduled",
      "message": "Reminder: Your appointment is in 2 hours..."
    }
  ]
}
```

**✅ Success Criteria:**
- Status: 200
- Shows 2 auto-generated reminders (24h + 2h before)
- Reminders status is "scheduled"
- Correct timing calculations

#### Test 2.4: Update Appointment Details
**Request:** `PUT /appointments/{{appointment_id}}`
```json
{
  "reason": "Annual checkup - updated",
  "staff_notes": "Patient requested longer session",
  "status": "confirmed"
}
```

**✅ Success Criteria:**
- Status: 200
- Appointment updated successfully
- Status changed to "confirmed"
- Reminders remain intact (no time change)

#### Test 2.5: Reschedule Appointment (Auto-Update Reminders)
**Request:** `POST /appointments/{{appointment_id}}/reschedule`
```json
{
  "new_datetime_start": "2025-06-21 14:00:00",
  "new_datetime_end": "2025-06-21 14:30:00",
  "reason": "Patient requested different time",
  "notes_by_staff": "Rescheduled per patient preference"
}
```

**Expected Behavior:**
1. Appointment time updated
2. **OLD reminders automatically cancelled**
3. **NEW reminders automatically scheduled** for new time

**✅ Success Criteria:**
- Status: 200
- Appointment datetime updated
- System logs show "Rescheduled reminders due to time change"
- New reminders: 2025-06-20 14:00:00 and 2025-06-21 12:00:00

#### Test 2.6: Verify Reminders Were Rescheduled
**Request:** `GET /appointments/{{appointment_id}}/reminders`

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "scheduled_time": "2025-06-20 14:00:00",
      "status": "scheduled"
    },
    {
      "scheduled_time": "2025-06-21 12:00:00", 
      "status": "scheduled"
    }
  ]
}
```

**✅ Success Criteria:**
- Status: 200
- Reminder times updated to match new appointment time
- Old reminders are cancelled or replaced

---

### 🔔 Scenario 3: Reminder Management

#### Test 3.1: Schedule Custom Reminder
**Request:** `POST /reminders/schedule`
```json
{
  "appointment_id": 123,
  "channels": ["email", "sms"],
  "reminder_times": [30, 60, 1440],
  "priority": "high",
  "custom_message": "Please bring insurance card and arrive 15 minutes early"
}
```

**✅ Success Criteria:**
- Status: 200
- 3 custom reminders scheduled (30min, 1h, 24h before)
- Both email and SMS channels configured
- Custom message applied

#### Test 3.2: Send Test Reminder
**Request:** `POST /reminders/test`
```json
{
  "appointment_id": 123,
  "channel": "email",
  "message": "Test reminder - please confirm receipt"
}
```

**✅ Success Criteria:**
- Status: 200
- Test email sent immediately
- Delivery confirmed in logs

#### Test 3.3: View Reminder Logs
**Request:** `GET /reminders/logs?start_date=2025-06-15&end_date=2025-06-15`

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "appointment_id": 123,
      "channel": "email",
      "status": "delivered",
      "sent_at": "2025-06-15 10:30:00",
      "message": "Test reminder..."
    }
  ]
}
```

**✅ Success Criteria:**
- Status: 200
- Shows delivery history
- Includes status (delivered/failed/pending)
- Filterable by date range

#### Test 3.4: Cancel Appointment (Auto-Cancel Reminders)
**Request:** `POST /appointments/{{appointment_id}}/cancel`
```json
{
  "reason": "Patient requested cancellation"
}
```

**Expected Behavior:**
1. Appointment status → "cancelled"
2. **ALL reminders automatically cancelled**
3. Patient receives NO future reminder spam

**✅ Success Criteria:**
- Status: 200
- Appointment status changed to "cancelled"
- System logs show "Cancelled all reminders for appointment"
- Future reminders will not be sent

#### Test 3.5: Verify Reminders Were Cancelled
**Request:** `GET /appointments/{{appointment_id}}/reminders`

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status": "cancelled",
      "cancelled_at": "2025-06-15 10:45:00"
    },
    {
      "id": 2,
      "status": "cancelled", 
      "cancelled_at": "2025-06-15 10:45:00"
    }
  ]
}
```

**✅ Success Criteria:**
- Status: 200
- All reminders show "cancelled" status
- Cancellation timestamp present

---

### 🏥 Scenario 4: Daily Workflow Simulation

#### Test 4.1: Morning Routine
1. **Check Today's Schedule:** `GET /appointments?today=true&status=confirmed`
2. **Review Pending Appointments:** `GET /appointments?status=pending`
3. **Confirm Pending Appointments:** `POST /appointments/{id}/confirm`

#### Test 4.2: Patient Call-In Scenario
1. **Patient Requests Reschedule:** Use reschedule endpoint
2. **Verify Slot Availability:** `GET /appointments/slots/available`
3. **Update Appointment:** Use reschedule endpoint
4. **Confirm Auto-Updated Reminders:** Check reminders endpoint

#### Test 4.3: Emergency Procedures
1. **Doctor Emergency:** Use cancel endpoint with emergency reason
2. **Send Emergency Notification:** Use test reminder with urgent message
3. **Verify All Reminders Cancelled:** Check reminder status

#### Test 4.4: End-of-Day Reporting
1. **Daily Summary:** `GET /appointments?date={{current_date}}`
2. **Reminder Report:** `GET /reminders/logs?start_date={{current_date}}`
3. **Analytics:** `GET /reminders/analytics` (if permissions allow)

---

### 🔒 Scenario 5: Error Handling & Security

#### Test 5.1: Invalid Data Validation
**Request:** `POST /appointments`
```json
{
  "patient_id": "invalid",
  "doctor_id": 999,
  "appointment_datetime_start": "2020-01-01 10:00:00"
}
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "patient_id": ["The patient id must be a number."],
    "doctor_id": ["The selected doctor id is invalid."],
    "appointment_datetime_start": ["The appointment datetime start must be a date after now."]
  }
}
```

**✅ Success Criteria:**
- Status: 422
- Detailed validation error messages
- No appointment created

#### Test 5.2: Unauthorized Access
**Request:** `GET /appointments` (No Authorization header)

**✅ Success Criteria:**
- Status: 401
- Error message about missing authentication

#### Test 5.3: Non-Existent Resource
**Request:** `GET /appointments/99999`

**✅ Success Criteria:**
- Status: 404
- Appointment not found message

#### Test 5.4: Invalid Reminder Configuration
**Request:** `POST /reminders/schedule`
```json
{
  "appointment_id": "invalid",
  "channels": ["carrier_pigeon"],
  "reminder_times": [99999]
}
```

**✅ Success Criteria:**
- Status: 422
- Validation errors for invalid channels and excessive reminder times

---

## 📊 Success Metrics

### Overall System Health
- ✅ All CRUD operations return expected status codes
- ✅ Authentication works consistently
- ✅ Automatic reminders trigger on appointment events
- ✅ Error responses are informative and helpful

### Automation Verification
- ✅ **Create appointment** → reminders automatically scheduled
- ✅ **Reschedule appointment** → reminders automatically updated
- ✅ **Cancel appointment** → reminders automatically cancelled
- ✅ **No manual intervention** required for routine operations

### Data Integrity
- ✅ Appointment data persists correctly
- ✅ Reminder schedules align with appointment times
- ✅ Patient preferences are respected
- ✅ Cancellations prevent reminder spam

## 🚨 Troubleshooting Common Issues

### Issue: 401 Unauthorized
**Solution:** Re-run the login request to refresh the JWT token

### Issue: 422 Validation Errors
**Solution:** Check request body format and required fields

### Issue: No Reminders Generated
**Possible Causes:**
- Patient missing contact information
- Queue worker not running
- Invalid patient reminder preferences

**Solution:** 
```bash
# Check queue worker
php artisan queue:work --once

# Verify patient data
GET /patients/{id} - check email/phone fields
```

### Issue: Reminders Not Auto-Updating
**Possible Causes:**
- Observer not properly configured
- Database transaction issues

**Solution:**
- Check Laravel logs for Observer errors
- Verify appointment update operations complete successfully

## 📈 Performance Benchmarks

### Expected Response Times
- Authentication: < 200ms
- Appointment CRUD: < 300ms
- Reminder operations: < 500ms
- Bulk operations: < 2000ms

### Throughput Expectations
- Concurrent appointment bookings: 10-20/minute
- Reminder delivery: 100-500/minute
- Report generation: < 5 seconds for monthly data

## 🎉 Test Completion Checklist

- [ ] All authentication tests pass
- [ ] All appointment CRUD operations work
- [ ] Automatic reminder scheduling verified
- [ ] Automatic reminder updates on reschedule work
- [ ] Automatic reminder cancellation on appointment cancel works
- [ ] Custom reminder scheduling works
- [ ] Test reminders deliver successfully
- [ ] Error handling returns appropriate responses
- [ ] Security restrictions prevent unauthorized access
- [ ] Daily workflow scenarios complete successfully

**🎯 Target: 100% test pass rate with all automation features working correctly**

---

## 📞 Support & Documentation

### Additional Resources
- **API Documentation:** Check `/docs` endpoint if available
- **Database Schema:** Review migration files
- **Queue Configuration:** Check `config/queue.php`
- **Reminder Settings:** Review `config/reminders.php`

### Debugging Tips
- Enable Laravel debug mode during testing
- Monitor queue worker output for reminder processing
- Check application logs for Observer events
- Use database queries to verify data persistence

**Happy Testing! 🧪✨**
