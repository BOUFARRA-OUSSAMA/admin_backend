# Doctor Profile API Testing

## Test the new endpoints with curl commands:

### 1. Get Available Specialties (Public - No Auth)
```bash
curl -X GET "http://localhost:8000/api/specialties" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"
```

### 2. Get Doctor Profile (Requires Auth)
```bash
curl -X GET "http://localhost:8000/api/doctor/profile" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 3. Update Doctor Profile (Requires Auth)
```bash
curl -X PUT "http://localhost:8000/api/doctor/profile" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "name": "Dr. John Smith",
    "email": "john.smith@example.com",
    "phone": "+1234567890",
    "specialty": "Cardiology",
    "experience_years": 10,
    "consultation_fee": 150.00,
    "is_available": true,
    "max_patient_appointments": 20,
    "working_hours": {
      "monday": ["09:00", "17:00"],
      "tuesday": ["09:00", "17:00"],
      "wednesday": ["09:00", "17:00"],
      "thursday": ["09:00", "17:00"],
      "friday": ["09:00", "17:00"],
      "saturday": ["09:00", "13:00"],
      "sunday": null
    }
  }'
```

### 4. Update Profile Image (Requires Auth)
```bash
curl -X POST "http://localhost:8000/api/doctor/profile/image" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "image=@/path/to/your/image.jpg"
```

## Expected Responses:

### Success Response Format:
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data here
  }
}
```

### Error Response Format:
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    // Validation errors if any
  }
}
```

## Routes Added:
- GET /api/doctor/profile
- PUT /api/doctor/profile  
- POST /api/doctor/profile/image
- GET /api/specialties (public)
