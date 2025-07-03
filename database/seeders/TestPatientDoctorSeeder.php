<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\VitalSign;
use App\Models\LabTest;
use App\Models\LabResult;
use App\Models\PatientNote;
use App\Models\PatientAlert;
use App\Models\PatientFile;
use App\Models\Appointment;
use App\Models\TimelineEvent;
use Carbon\Carbon;
use DB;

class TestPatientDoctorSeeder extends Seeder
{
    public function run()
    {
        // Fetch users
        $patientUser = User::where('email', 'patient@example.com')->first();
        $doctorUser = User::where('email', 'doctor@example.com')->first();
        if (!$patientUser || !$doctorUser) {
            $this->command->error('Test users not found.');
            return;
        }
        $patient = Patient::where('user_id', $patientUser->id)->first();
        $doctor = Doctor::where('user_id', $doctorUser->id)->first();
        if (!$patient || !$doctor) {
            $this->command->error('Patient or Doctor record not found.');
            return;
        }

        // 1. Vital Signs
        VitalSign::create([
            'patient_id' => $patient->id,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'pulse_rate' => 72,
            'temperature' => 36.8,
            'temperature_unit' => '°C',
            'respiratory_rate' => 16,
            'oxygen_saturation' => 98,
            'weight' => 70.5,
            'weight_unit' => 'kg',
            'height' => 175,
            'height_unit' => 'cm',
            'notes' => 'Routine checkup',
            'recorded_by_user_id' => $doctorUser->id,
            'recorded_at' => Carbon::now()->subDays(2),
        ]);
        VitalSign::create([
            'patient_id' => $patient->id,
            'blood_pressure_systolic' => 118,
            'blood_pressure_diastolic' => 78,
            'pulse_rate' => 70,
            'temperature' => 36.7,
            'temperature_unit' => '°C',
            'respiratory_rate' => 15,
            'oxygen_saturation' => 99,
            'weight' => 70.0,
            'weight_unit' => 'kg',
            'height' => 175,
            'height_unit' => 'cm',
            'notes' => 'Follow-up visit',
            'recorded_by_user_id' => $doctorUser->id,
            'recorded_at' => Carbon::now()->subDay(),
        ]);

        // 2. Prescriptions (Medications)
        DB::table('prescriptions')->insert([
            [
                'patient_id' => $patient->id,
                'doctor_user_id' => $doctorUser->id,
                'medication_name' => 'Atorvastatin',
                'dosage' => '10mg',
                'frequency' => 'Once daily',
                'duration' => '30 days',
                'start_date' => Carbon::now()->subDays(10)->toDateString(),
                'end_date' => null,
                'instructions' => 'Take with food',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'patient_id' => $patient->id,
                'doctor_user_id' => $doctorUser->id,
                'medication_name' => 'Metformin',
                'dosage' => '500mg',
                'frequency' => 'Twice daily',
                'duration' => '60 days',
                'start_date' => Carbon::now()->subDays(5)->toDateString(),
                'end_date' => null,
                'instructions' => 'Take after meals',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // 3. Lab Tests & Results
        $labTest = LabTest::create([
            'patient_id' => $patient->id,
            'requested_by_user_id' => $doctorUser->id,
            'test_name' => 'Complete Blood Count',
            'test_code' => 'CBC',
            'urgency' => 'routine',
            'requested_date' => Carbon::now()->subDays(3),
            'scheduled_date' => Carbon::now()->subDays(2),
            'lab_name' => 'Central Lab',
            'status' => 'completed',
            'chart_patient_id' => null,
        ]);
        LabResult::create([
            'lab_test_id' => $labTest->id,
            'result_date' => Carbon::now()->subDays(2)->toDateString(),
            'performed_by_lab_name' => 'Central Lab',
            'structured_results' => [
                'WBC' => '6.0',
                'RBC' => '4.8',
                'Hemoglobin' => '14.2'
            ],
            'interpretation' => 'Normal',
            'status' => 'reviewed',
            'created_at' => now(),
            'updated_at' => now(),
            'patient_id' => $patient->id,
        ]);

        // 4. Patient Notes
        PatientNote::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorUser->id,
            'note_type' => 'general',
            'title' => 'Initial Consultation',
            'content' => 'Patient is responding well to medication.',
            'is_private' => false,
            'created_at' => Carbon::now()->subDay(),
        ]);
        PatientNote::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorUser->id,
            'note_type' => 'follow_up',
            'title' => 'Follow-up Plan',
            'content' => 'Recommended follow-up in 2 weeks.',
            'is_private' => false,
            'created_at' => Carbon::now(),
        ]);

        // 5. Patient Alerts
        PatientAlert::create([
            'patient_id' => $patient->id,
            'alert_type' => 'allergy',
            'severity' => 'high',
            'title' => 'Penicillin Allergy',
            'description' => 'Allergic to penicillin.',
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(7),
        ]);

        // 6. Patient Files
        PatientFile::create([
            'patient_id' => $patient->id,
            'uploaded_by_user_id' => $doctorUser->id,
            'file_type' => 'image',
            'category' => 'xray',
            'original_filename' => 'xray_june2025.jpg',
            'stored_filename' => 'xray_june2025_uuid.jpg',
            'file_path' => 'files/xray_june2025.jpg',
            'file_size' => 123456,
            'mime_type' => 'image/jpeg',
            'description' => 'Chest X-ray',
            'is_visible_to_patient' => true,
            'uploaded_at' => Carbon::now()->subDays(1),
        ]);
        PatientFile::create([
            'patient_id' => $patient->id,
            'uploaded_by_user_id' => $doctorUser->id,
            'file_type' => 'document',
            'category' => 'lab_report',
            'original_filename' => 'lab_report_june2025.pdf',
            'stored_filename' => 'lab_report_june2025_uuid.pdf',
            'file_path' => 'files/lab_report_june2025.pdf',
            'file_size' => 45678,
            'mime_type' => 'application/pdf',
            'description' => 'Lab report',
            'is_visible_to_patient' => true,
            'uploaded_at' => Carbon::now(),
        ]);

        // 7. Appointments
        Appointment::create([
            'patient_user_id' => $patientUser->id,
            'doctor_user_id' => $doctorUser->id,
            'appointment_datetime_start' => Carbon::now()->addDays(3),
            'appointment_datetime_end' => Carbon::now()->addDays(3)->addHour(),
            'type' => 'consultation',
            'status' => 'scheduled',
            'booked_by_user_id' => $doctorUser->id,
            'last_updated_by_user_id' => $doctorUser->id,
        ]);
        Appointment::create([
            'patient_user_id' => $patientUser->id,
            'doctor_user_id' => $doctorUser->id,
            'appointment_datetime_start' => Carbon::now()->subDays(5),
            'appointment_datetime_end' => Carbon::now()->subDays(5)->addHour(),
            'type' => 'consultation',
            'status' => 'completed',
            'booked_by_user_id' => $doctorUser->id,
            'last_updated_by_user_id' => $doctorUser->id,
        ]);

        // 8. Timeline Events
        TimelineEvent::create([
            'patient_id' => $patient->id,
            'event_type' => 'appointment',
            'title' => 'Appointment Scheduled',
            'description' => 'Appointment scheduled with Dr. Smith.',
            'event_date' => Carbon::now()->addDays(3),
            'related_id' => null,
            'related_type' => null,
            'importance' => 'medium',
            'is_visible_to_patient' => true,
            'created_by_user_id' => $doctorUser->id,
        ]);
        TimelineEvent::create([
            'patient_id' => $patient->id,
            'event_type' => 'file_upload',
            'title' => 'X-ray Uploaded',
            'description' => 'X-ray uploaded.',
            'event_date' => Carbon::now()->subDays(1),
            'related_id' => null,
            'related_type' => null,
            'importance' => 'low',
            'is_visible_to_patient' => true,
            'created_by_user_id' => $doctorUser->id,
        ]);
    }
}
