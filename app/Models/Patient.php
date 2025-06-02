<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Patient extends Model
{
    use HasFactory;
    protected $table = 'patients';
    protected $fillable = [
        'user_id','registration_date'
    ];

    protected $casts = [
        'registration_date' => 'date', // Assurez-vous que c'est le bon cast (date ou datetime)
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function personalInfo()
    {
        return $this->hasOne(PersonalInfo::class, 'patient_id');
    }

    public function medicalHistory()
    {
        return $this->hasOne(MedicalHistory::class, 'patient_id'); // Décommenté et patient_id ajouté
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'patient_id');
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSign::class, 'patient_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'patient_id');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'patient_id');
    }

 
}
