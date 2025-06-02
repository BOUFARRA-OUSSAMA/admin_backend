<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
class Doctor extends Model
{
    protected $table = 'doctor_profiles';

    protected $fillable = [
             'user_id',
        'specialty',
        'license_number',
        'education',
        'experience',
        'availability_notes',
        'is_active',
    ];

    
    /**
     * Relation : un profil appartient à un utilisateur.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Optionnel : scope pour obtenir les profils actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
