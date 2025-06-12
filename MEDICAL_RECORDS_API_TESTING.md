# 🏥 MEDICAL RECORDS API TESTING GUIDE
## Complete Testing Documentation for Frontend Integration

**Date:** June 11, 2025  
**Phase:** Doctor Patient Management - Complete API Testing  
**Purpose:** Frontend Integration & API Validation

---

## 🔑 **AUTHENTICATION SETUP**

### 1. Login as Doctor
```http
POST {{base_url}}/api/auth/login
Content-Type: application/json

{
  "email": "doctor@example.com",
  "password": "password123"
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": 2,
      "name": "Dr. John Smith",
      "email": "doctor@example.com",
      "role": "doctor"
    }
  }
}
```

### 2. Login as Patient
```http
POST {{base_url}}/api/auth/login
Content-Type: application/json

{
  "email": "patient@example.com",
  "password": "password123"
}
```

**Save tokens as:**
- `{{doctor_token}}` = Doctor's access_token
- `{{patient_token}}` = Patient's access_token
- `{{patient_id}}` = Patient ID (e.g., 1)

---

## 📊 **PATIENT MEDICAL DATA ENDPOINTS**

### 1. Get Patient Medical Summary
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/summary
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "basic_info": {
      "id": 1,
      "user_id": 3,
      "full_name": "Jane Doe",
      "email": "patient@example.com",
      "phone": "+1234567890",
      "age": 35,
      "gender": "female",
      "registration_date": "2025-01-15"
    },
    "statistics": {
      "total_appointments": 12,
      "upcoming_appointments": 2,
      "active_medications": 3,
      "active_alerts": 1,
      "total_files": 8,
      "recent_vitals_count": 5,
      "lab_results_this_year": 4,
      "last_visit": "2025-06-01T14:30:00Z",
      "next_appointment": "2025-06-15T10:00:00Z"
    },
    "recent_vitals": [
      {
        "id": 1,
        "blood_pressure_systolic": 120,
        "blood_pressure_diastolic": 80,
        "pulse_rate": 72,
        "temperature": 98.6,
        "recorded_at": "2025-06-10T09:00:00Z",
        "recorded_by": "Dr. John Smith"
      }
    ],
    "active_medications": [
      {
        "id": 1,
        "medication_name": "Lisinopril",
        "dosage": "10mg",
        "frequency": "once daily",
        "status": "active",
        "prescribed_date": "2025-05-01T00:00:00Z",
        "doctor_name": "Dr. John Smith"
      }
    ],
    "active_alerts": [
      {
        "id": 1,
        "alert_type": "allergy",
        "title": "Penicillin Allergy",
        "message": "Patient is allergic to Penicillin",
        "severity": "high",
        "is_active": true
      }
    ],
    "timeline_events": [
      {
        "id": 1,
        "type": "appointment",
        "title": "Regular Checkup",
        "description": "Routine examination completed",
        "event_date": "2025-06-01T14:30:00Z",
        "importance": "medium",
        "created_by": "Dr. John Smith"
      }
    ]
  },
  "message": "Patient medical summary retrieved successfully"
}
```

### 2. Get Patient Vital Signs
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/vitals?limit=10
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "blood_pressure_systolic": 120,
      "blood_pressure_diastolic": 80,
      "pulse_rate": 72,
      "temperature": 98.6,
      "respiratory_rate": 16,
      "oxygen_saturation": 98,
      "weight": 65.5,
      "height": 165.0,
      "recorded_at": "2025-06-10T09:00:00Z",
      "recorded_by": "Dr. John Smith",
      "notes": "Normal vital signs"
    }
  ],
  "message": "Patient vital signs retrieved successfully"
}
```

### 3. Get Patient Medications
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/medications
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "medication_name": "Lisinopril",
      "dosage": "10mg",
      "frequency": "once daily",
      "route": "oral",
      "status": "active",
      "prescribed_date": "2025-05-01T00:00:00Z",
      "start_date": "2025-05-01",
      "end_date": null,
      "refills_allowed": 5,
      "refills_used": 1,
      "doctor_name": "Dr. John Smith",
      "notes": "For blood pressure management"
    }
  ],
  "message": "Patient medications retrieved successfully"
}
```

### 4. Get Patient Lab Results
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/lab-results?limit=10
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "lab_test": {
        "id": 1,
        "test_name": "Complete Blood Count",
        "test_code": "CBC"
      },
      "result_date": "2025-06-05T00:00:00Z",
      "structured_results": {
        "test_name": "Complete Blood Count",
        "results": [
          {
            "parameter": "Hemoglobin",
            "value": "14.2",
            "unit": "g/dL",
            "reference_range": "12.0-15.5",
            "status": "normal"
          },
          {
            "parameter": "White Blood Cell Count",
            "value": "7.2",
            "unit": "K/uL",
            "reference_range": "4.5-11.0",
            "status": "normal"
          }
        ]
      },
      "interpretation": "All values within normal limits",
      "status": "completed",
      "ordered_by": "Dr. John Smith"
    }
  ],
  "message": "Patient lab results retrieved successfully"
}
```

### 5. Get Patient Timeline
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/timeline?limit=20
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "appointment",
      "title": "Regular Checkup",
      "description": "Routine examination completed by Dr. John Smith",
      "event_date": "2025-06-01T14:30:00Z",
      "importance": "medium",
      "is_visible_to_patient": true,
      "created_by": "Dr. John Smith",
      "icon": "calendar",
      "color": "primary"
    },
    {
      "id": 2,
      "type": "prescription",
      "title": "New Medication Prescribed",
      "description": "Lisinopril 10mg prescribed for blood pressure management",
      "event_date": "2025-05-01T10:00:00Z",
      "importance": "medium",
      "is_visible_to_patient": true,
      "created_by": "Dr. John Smith",
      "icon": "pills",
      "color": "success"
    }
  ],
  "message": "Patient timeline retrieved successfully"
}
```

### 6. Get Patient Files
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/files
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "filename": "chest_xray_2025_06_01.jpg",
      "original_filename": "ChestXRay.jpg",
      "file_type": "image",
      "mime_type": "image/jpeg",
      "file_size": 2048576,
      "category": "xray",
      "description": "Chest X-ray for routine examination",
      "uploaded_at": "2025-06-01T15:00:00Z",
      "uploaded_by": "Patient",
      "download_url": "/api/patient-files/1/download",
      "thumbnail_url": "/storage/patient-files/thumbnails/1.jpg"
    },
    {
      "id": 2,
      "filename": "insurance_card_2025.pdf",
      "original_filename": "Insurance_Card.pdf",
      "file_type": "document",
      "mime_type": "application/pdf",
      "file_size": 512000,
      "category": "insurance",
      "description": "Updated insurance card",
      "uploaded_at": "2025-05-15T10:30:00Z",
      "uploaded_by": "Patient",
      "download_url": "/api/patient-files/2/download"
    }
  ],
  "message": "Patient files retrieved successfully"
}
```

### 7. Get Patient Notes
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/notes
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "note_type": "diagnosis",
      "title": "Hypertension Diagnosis",
      "content": "Patient diagnosed with stage 1 hypertension. Blood pressure consistently elevated over 140/90. Lifestyle modifications recommended along with medication.",
      "is_private": false,
      "created_at": "2025-05-01T10:00:00Z",
      "doctor_name": "Dr. John Smith",
      "tags": ["hypertension", "diagnosis"]
    },
    {
      "id": 2,
      "note_type": "follow_up",
      "title": "Follow-up Visit",
      "content": "Patient responding well to Lisinopril. Blood pressure improved to 125/82. Continue current medication.",
      "is_private": false,
      "created_at": "2025-06-01T14:30:00Z",
      "doctor_name": "Dr. John Smith",
      "tags": ["follow-up", "medication-response"]
    }
  ],
  "message": "Patient notes retrieved successfully"
}
```

### 8. Get Patient Alerts
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/alerts
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "alert_type": "allergy",
      "title": "Penicillin Allergy",
      "message": "Patient has a documented severe allergy to Penicillin. Avoid all penicillin-based antibiotics.",
      "severity": "high",
      "is_active": true,
      "created_at": "2025-01-15T00:00:00Z",
      "created_by": "Dr. Sarah Wilson",
      "color": "danger",
      "icon": "exclamation-triangle"
    }
  ],
  "message": "Patient alerts retrieved successfully"
}
```

---

## 🩺 **VITAL SIGNS MANAGEMENT**

### 1. Create Vital Signs
```http
POST {{base_url}}/api/vital-signs
Authorization: Bearer {{doctor_token}}
Content-Type: application/json

{
  "patient_id": {{patient_id}},
  "blood_pressure_systolic": 125,
  "blood_pressure_diastolic": 82,
  "pulse_rate": 75,
  "temperature": 98.7,
  "respiratory_rate": 16,
  "oxygen_saturation": 98,
  "weight": 66.0,
  "height": 165.0,
  "notes": "Patient feeling well, slight increase in weight"
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "patient_id": 1,
    "blood_pressure_systolic": 125,
    "blood_pressure_diastolic": 82,
    "pulse_rate": 75,
    "temperature": 98.7,
    "respiratory_rate": 16,
    "oxygen_saturation": 98,
    "weight": 66.0,
    "height": 165.0,
    "recorded_at": "2025-06-11T10:00:00Z",
    "recorded_by": "Dr. John Smith",
    "notes": "Patient feeling well, slight increase in weight"
  },
  "message": "Vital signs recorded successfully"
}
```

### 2. Get All Vital Signs
```http
GET {{base_url}}/api/vital-signs?patient_id={{patient_id}}&limit=10
Authorization: Bearer {{doctor_token}}
```

---

## 💊 **MEDICATIONS MANAGEMENT**

### 1. Create Medication
```http
POST {{base_url}}/api/medications
Authorization: Bearer {{doctor_token}}
Content-Type: application/json

{
  "patient_id": {{patient_id}},
  "medication_name": "Metformin",
  "dosage": "500mg",
  "frequency": "twice daily",
  "route": "oral",
  "start_date": "2025-06-11",
  "duration_days": 90,
  "refills_allowed": 3,
  "notes": "Take with meals to reduce stomach upset. Monitor blood glucose levels."
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "patient_id": 1,
    "medication_name": "Metformin",
    "dosage": "500mg",
    "frequency": "twice daily",
    "route": "oral",
    "status": "active",
    "prescribed_date": "2025-06-11T10:00:00Z",
    "start_date": "2025-06-11",
    "end_date": "2025-09-09",
    "refills_allowed": 3,
    "refills_used": 0,
    "doctor_name": "Dr. John Smith",
    "notes": "Take with meals to reduce stomach upset. Monitor blood glucose levels."
  },
  "message": "Medication prescribed successfully"
}
```

### 2. Discontinue Medication
```http
POST {{base_url}}/api/medications/1/discontinue
Authorization: Bearer {{doctor_token}}
Content-Type: application/json

{
  "reason": "Patient developed side effects",
  "notes": "Patient reported dizziness and fatigue. Switching to alternative medication."
}
```

---

## 🧪 **LAB RESULTS MANAGEMENT**

### 1. Create Lab Result
```http
POST {{base_url}}/api/lab-results
Authorization: Bearer {{doctor_token}}
Content-Type: application/json

{
  "patient_id": {{patient_id}},
  "lab_test_name": "Lipid Panel",
  "result_date": "2025-06-10",
  "structured_results": {
    "test_name": "Lipid Panel",
    "results": [
      {
        "parameter": "Total Cholesterol",
        "value": "195",
        "unit": "mg/dL",
        "reference_range": "<200",
        "status": "normal"
      },
      {
        "parameter": "LDL Cholesterol",
        "value": "120",
        "unit": "mg/dL",
        "reference_range": "<100",
        "status": "high"
      },
      {
        "parameter": "HDL Cholesterol",
        "value": "45",
        "unit": "mg/dL",
        "reference_range": ">40",
        "status": "normal"
      },
      {
        "parameter": "Triglycerides",
        "value": "150",
        "unit": "mg/dL",
        "reference_range": "<150",
        "status": "borderline"
      }
    ]
  },
  "interpretation": "LDL cholesterol slightly elevated. Recommend dietary modifications and recheck in 3 months.",
  "status": "completed"
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "patient_id": 1,
    "lab_test": {
      "id": 2,
      "test_name": "Lipid Panel",
      "test_code": "LIPID"
    },
    "result_date": "2025-06-10T00:00:00Z",
    "structured_results": {
      "test_name": "Lipid Panel",
      "results": [
        {
          "parameter": "Total Cholesterol",
          "value": "195",
          "unit": "mg/dL",
          "reference_range": "<200",
          "status": "normal"
        }
      ]
    },
    "interpretation": "LDL cholesterol slightly elevated. Recommend dietary modifications and recheck in 3 months.",
    "status": "completed",
    "ordered_by": "Dr. John Smith"
  },
  "message": "Lab result created successfully"
}
```

---

## 📝 **PATIENT NOTES MANAGEMENT**

### 1. Create Patient Note
```http
POST {{base_url}}/api/patient-notes
Authorization: Bearer {{doctor_token}}
Content-Type: application/json

{
  "patient_id": {{patient_id}},
  "note_type": "treatment",
  "title": "Treatment Plan Update",
  "content": "Based on recent lab results, adjusting treatment plan:\n\n1. Continue Lisinopril 10mg daily\n2. Add Metformin 500mg twice daily\n3. Dietary consultation scheduled\n4. Follow-up in 6 weeks\n\nPatient counseled on lifestyle modifications including diet and exercise.",
  "is_private": false,
  "tags": ["treatment-plan", "medication-adjustment", "lifestyle"]
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "patient_id": 1,
    "note_type": "treatment",
    "title": "Treatment Plan Update",
    "content": "Based on recent lab results, adjusting treatment plan...",
    "is_private": false,
    "created_at": "2025-06-11T10:30:00Z",
    "updated_at": "2025-06-11T10:30:00Z",
    "doctor_name": "Dr. John Smith",
    "tags": ["treatment-plan", "medication-adjustment", "lifestyle"]
  },
  "message": "Patient note created successfully"
}
```

### 2. Get Note Types
```http
GET {{base_url}}/api/patient-note-types
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    { "value": "diagnosis", "label": "Diagnosis" },
    { "value": "treatment", "label": "Treatment Plan" },
    { "value": "follow_up", "label": "Follow-up" },
    { "value": "consultation", "label": "Consultation" },
    { "value": "referral", "label": "Referral" },
    { "value": "general", "label": "General Note" }
  ]
}
```

---

## 🚨 **PATIENT ALERTS MANAGEMENT**

### 1. Create Patient Alert
```http
POST {{base_url}}/api/patient-alerts
Authorization: Bearer {{doctor_token}}
Content-Type: application/json

{
  "patient_id": {{patient_id}},
  "alert_type": "drug_interaction",
  "title": "Drug Interaction Warning",
  "message": "Patient is taking Lisinopril and Metformin. Monitor for potential hypoglycemic episodes, especially during initial treatment phase.",
  "severity": "medium",
  "is_active": true,
  "expires_at": "2025-09-11T00:00:00Z"
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "patient_id": 1,
    "alert_type": "drug_interaction",
    "title": "Drug Interaction Warning",
    "message": "Patient is taking Lisinopril and Metformin. Monitor for potential hypoglycemic episodes...",
    "severity": "medium",
    "is_active": true,
    "created_at": "2025-06-11T10:45:00Z",
    "expires_at": "2025-09-11T00:00:00Z",
    "created_by": "Dr. John Smith",
    "color": "warning",
    "icon": "exclamation-circle"
  },
  "message": "Patient alert created successfully"
}
```

---

## 📁 **FILE UPLOAD & MANAGEMENT**

### 1. Upload Medical Image (X-ray, MRI, etc.)
```http
POST {{base_url}}/api/patient-files
Authorization: Bearer {{patient_token}}
Content-Type: multipart/form-data

{
  "patient_id": {{patient_id}},
  "file": [Binary file data - chest_xray.jpg],
  "category": "xray",
  "description": "Chest X-ray for follow-up examination",
  "file_type": "image"
}
```

**Sample Files for Testing:**
- **chest_xray.jpg** (JPEG image, <10MB)
- **blood_test_results.pdf** (PDF document, <25MB)
- **mri_scan.dicom** (DICOM file, <50MB)

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "patient_id": 1,
    "filename": "chest_xray_2025_06_11_104500.jpg",
    "original_filename": "chest_xray.jpg",
    "file_type": "image",
    "mime_type": "image/jpeg",
    "file_size": 1842176,
    "category": "xray",
    "description": "Chest X-ray for follow-up examination",
    "uploaded_at": "2025-06-11T10:45:00Z",
    "uploaded_by": "Patient",
    "download_url": "/api/patient-files/3/download",
    "thumbnail_url": "/storage/patient-files/thumbnails/3.jpg"
  },
  "message": "File uploaded successfully"
}
```

### 2. Upload Medical Document
```http
POST {{base_url}}/api/patient-files
Authorization: Bearer {{patient_token}}
Content-Type: multipart/form-data

{
  "patient_id": {{patient_id}},
  "file": [Binary file data - lab_report.pdf],
  "category": "lab_report",
  "description": "Blood work results from external lab",
  "file_type": "document"
}
```

### 3. Get File Categories
```http
GET {{base_url}}/api/patient-files-categories
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    { "value": "xray", "label": "X-Ray", "type": "image" },
    { "value": "scan", "label": "CT/MRI Scan", "type": "image" },
    { "value": "lab_report", "label": "Lab Report", "type": "document" },
    { "value": "insurance", "label": "Insurance Documents", "type": "document" },
    { "value": "prescription", "label": "Prescription", "type": "document" },
    { "value": "referral", "label": "Referral Letter", "type": "document" },
    { "value": "other", "label": "Other", "type": "both" }
  ]
}
```

### 4. Download File
```http
GET {{base_url}}/api/patient-files/3/download
Authorization: Bearer {{doctor_token}}
```

**Expected Response:** Binary file data with appropriate headers

### 5. Delete File (Admin/Doctor only)
```http
DELETE {{base_url}}/api/patient-files/3
Authorization: Bearer {{doctor_token}}
```

---

## 📈 **TIMELINE EVENTS**

### 1. Get Timeline Events
```http
GET {{base_url}}/api/timeline-events?patient_id={{patient_id}}&limit=20
Authorization: Bearer {{doctor_token}}
```

### 2. Get Timeline Event Details
```http
GET {{base_url}}/api/timeline-events/1
Authorization: Bearer {{doctor_token}}
```

### 3. Get Timeline Summary
```http
GET {{base_url}}/api/timeline-events/summary?patient_id={{patient_id}}&days=30
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "period_days": 30,
    "total_events": 8,
    "events_by_type": {
      "appointment": 2,
      "prescription": 2,
      "vital_signs": 2,
      "file_upload": 1,
      "note": 1
    },
    "events_by_importance": {
      "high": 1,
      "medium": 5,
      "low": 2
    },
    "recent_events": [
      {
        "id": 8,
        "type": "file_upload",
        "title": "Medical Image Uploaded",
        "description": "Patient uploaded chest X-ray",
        "event_date": "2025-06-11T10:45:00Z",
        "importance": "low"
      }
    ]
  },
  "message": "Timeline summary retrieved successfully"
}
```

---

## 👨‍⚕️ **DOCTOR PATIENT MANAGEMENT**

### 1. Get Doctor's Patients
```http
GET {{base_url}}/api/doctor/patients/my-patients?limit=10&search=jane
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 3,
        "full_name": "Jane Doe",
        "email": "patient@example.com",
        "phone": "+1234567890",
        "age": 35,
        "gender": "female",
        "last_appointment": "2025-06-01T14:30:00Z",
        "next_appointment": "2025-06-15T10:00:00Z",
        "active_medications": 2,
        "critical_alerts": 1,
        "total_visits": 12
      }
    ],
    "per_page": 10,
    "total": 1
  },
  "message": "Doctor's patients retrieved successfully"
}
```

### 2. Get Patient Summary (Doctor View)
```http
GET {{base_url}}/api/doctor/patients/{{patient_id}}/summary
Authorization: Bearer {{doctor_token}}
```

### 3. Get Critical Alerts
```http
GET {{base_url}}/api/doctor/patients/alerts/critical
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "alert_id": 1,
      "patient_id": 1,
      "patient_name": "Jane Doe",
      "alert_type": "allergy",
      "title": "Penicillin Allergy",
      "message": "Patient has severe allergy to Penicillin",
      "severity": "high",
      "created_at": "2025-01-15T00:00:00Z"
    }
  ],
  "message": "Critical alerts retrieved successfully"
}
```

### 4. Get Dashboard Statistics
```http
GET {{base_url}}/api/doctor/patients/dashboard/stats
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total_patients": 25,
    "todays_appointments": 4,
    "weekly_appointments": 18,
    "critical_alerts": 3,
    "pending_lab_results": 2,
    "recent_activity": [
      {
        "type": "file_upload",
        "patient_name": "Jane Doe",
        "description": "Uploaded chest X-ray",
        "timestamp": "2025-06-11T10:45:00Z"
      }
    ],
    "generated_at": "2025-06-11T11:00:00Z"
  },
  "message": "Dashboard statistics retrieved successfully"
}
```

---

## 🧪 **PATIENT-SIDE TESTING**

### 1. Patient Views Own Medical Summary
```http
GET {{base_url}}/api/patients/{{patient_id}}/medical/summary
Authorization: Bearer {{patient_token}}
```

### 2. Patient Uploads File
```http
POST {{base_url}}/api/patient-files
Authorization: Bearer {{patient_token}}
Content-Type: multipart/form-data

{
  "patient_id": {{patient_id}},
  "file": [Binary file data],
  "category": "insurance",
  "description": "Updated insurance card for 2025"
}
```

### 3. Patient Cannot Access Doctor Endpoints
```http
GET {{base_url}}/api/doctor/patients/my-patients
Authorization: Bearer {{patient_token}}
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Insufficient permissions",
  "error": "Access denied"
}
```

---

## 🔒 **ERROR HANDLING TESTS**

### 1. Invalid Patient ID
```http
GET {{base_url}}/api/patients/999/medical/summary
Authorization: Bearer {{doctor_token}}
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Patient not found",
  "error": "No query results for model [App\\Models\\Patient] 999"
}
```

### 2. Missing Required Fields
```http
POST {{base_url}}/api/vital-signs
Authorization: Bearer {{doctor_token}}
Content-Type: application/json

{
  "blood_pressure_systolic": 120
}
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "patient_id": ["The patient id field is required."],
    "blood_pressure_diastolic": ["The blood pressure diastolic field is required."]
  }
}
```

### 3. File Too Large
```http
POST {{base_url}}/api/patient-files
Authorization: Bearer {{patient_token}}
Content-Type: multipart/form-data

{
  "patient_id": {{patient_id}},
  "file": [Large file > 25MB],
  "category": "xray"
}
```

**Expected Response:**
```json
{
  "success": false,
  "message": "File validation failed",
  "errors": {
    "file": ["The file may not be greater than 10240 kilobytes for images or 25600 kilobytes for documents."]
  }
}
```

---

## 📱 **FRONTEND INTEGRATION CHECKLIST**

### Required Environment Variables:
```typescript
// environment.ts
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api',
  maxFileSize: {
    image: 10485760, // 10MB
    document: 26214400 // 25MB
  },
  allowedFileTypes: {
    image: ['jpg', 'jpeg', 'png', 'gif'],
    document: ['pdf', 'doc', 'docx']
  }
};
```

## 🎯 **TESTING SEQUENCE FOR COMPLETE WORKFLOW**

1. **Authentication** - Login as doctor and patient
2. **View Patient Summary** - Get comprehensive overview
3. **Add Vital Signs** - Record new measurements
4. **Prescribe Medication** - Add new prescription
5. **Upload File** - Test file upload functionality
6. **Create Note** - Add doctor's note
7. **Set Alert** - Create patient alert
8. **View Timeline** - Check auto-generated events
9. **Patient Access** - Test patient-side views
10. **Error Handling** - Test validation and security

