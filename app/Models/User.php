<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany; // ✅ Add this import
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // ✅ Add this import
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',
        'profile_image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * User has many roles relationship
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withTimestamps();
    }

    /**
     * User has many AI models relationship
     */
    public function aiModels(): BelongsToMany
    {
        return $this->belongsToMany(AiModel::class, 'user_ai_model')
            ->withTimestamps();
    }

    /**
     * User has many activity logs relationship
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles->where('code', $role)->count() > 0;
        }

        // Convert array to collection if needed
        if (is_array($role)) {
            $role = collect($role);
        }

        return $role->intersect($this->roles->pluck('code'))->count() > 0;
    }

    /**
     * Get all permissions for the user through their roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        $permissions = collect();
        
        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }
        
        return $permissions->unique('id');
    }

    /**
     * Check if user has a specific permission.
     *
     * @param string $permissionCode
     * @return bool
     */
    public function hasPermission($permissionCode)
    {
        return $this->getAllPermissions()->contains('code', $permissionCode);
    }

    /**
     * Get the user's interface permission.
     *
     * @return \App\Models\Permission|null
     */
    public function getInterfacePermission()
    {
        $interfacePermissions = $this->getAllPermissions()->filter(function ($permission) {
            return $permission->isInterfacePermission();
        });
        
        return $interfacePermissions->first();
    }

    /**
     * Get the interface name for this user.
     *
     * @return string|null
     */
    public function getInterfaceName()
    {
        $permission = $this->getInterfacePermission();
        return $permission ? $permission->getInterfaceName() : null;
    }

    /**
     * Check if user is patient
     */
    public function isPatient(): bool
    {
        return $this->hasRole('patient');
    }



 

    /**
     * Check if user is doctor
     */
    public function isDoctor(): bool
    {
        return $this->hasRole('doctor');
    }

    /**
     * Check if user is receptionist
     */
    public function isReceptionist(): bool
    {
        return $this->hasRole('receptionist');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get the patient profile for this user.
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Get the doctor profile for this user.
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * ✅ DIRECT: Patient appointments relationship
     */
    public function patientAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_user_id');
    }

    /**
     * ✅ DIRECT: Doctor appointments relationship
     */
    public function doctorAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_user_id');
    }

    /**
     * ✅ SIMPLIFIED: Get appointments based on user role
     */
    public function getAppointmentsAttribute()
    {
        if ($this->isPatient()) {
            return $this->patientAppointments;
        }
        
        if ($this->isDoctor()) {
            return $this->doctorAppointments;
        }
        
        return collect();
    }
}