<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withTimestamps();
    }

    /**
     * User has many AI models relationship
     */
    public function aiModels()
    {
        return $this->belongsToMany(AiModel::class, 'user_ai_model')
            ->withTimestamps();
    }

    /**
     * User has many activity logs relationship
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->where('code', $role)->count() > 0;
        }

        return $role->intersect($this->roles)->count() > 0;
    }

    /**
     * Get all permissions for the user
     */
    public function getAllPermissions()
    {
        $permissions = [];
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission;
            }
        }

        return collect($permissions)->unique('id');
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->getAllPermissions()->where('code', $permission)->count() > 0;
        }

        return $permission->intersect($this->getAllPermissions())->count() > 0;
    }

    /**
     * Check if user is patient
     */
    public function isPatient()
    {
        return $this->hasRole('patient');
    }


    public function doctorProfile()
{
    return $this->hasOne(Doctor::class);
}
}
