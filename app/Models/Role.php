<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Define relationship with users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    /**
     * Define relationship with permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Get only interface permissions for this role.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function interfacePermissions()
    {
        return $this->permissions()->interfaces()->get();
    }

    /**
     * Get the single interface permission for this role.
     * Returns null if no interface permission is assigned.
     *
     * @return \App\Models\Permission|null
     */
    public function getInterfacePermission()
    {
        return $this->interfacePermissions()->first();
    }

    /**
     * Get the interface name for this role (e.g., 'admin', 'doctor').
     *
     * @return string|null
     */
    public function getInterfaceName()
    {
        $interfacePermission = $this->getInterfacePermission();
        return $interfacePermission ? $interfacePermission->getInterfaceName() : null;
    }

    /**
     * Check if role has exactly one interface permission as required.
     *
     * @return bool
     */
    public function hasValidInterfacePermission()
    {
        return $this->interfacePermissions()->count() === 1;
    }

    /**
     * Validate that role has exactly one interface permission.
     * Throws an exception if the validation fails.
     *
     * @throws \Exception If role has zero or multiple interface permissions
     * @return bool
     */
    public function validateInterfacePermission()
    {
        $count = $this->interfacePermissions()->count();
        
        if ($count === 0) {
            throw new \Exception("Role '{$this->name}' must have exactly one interface permission.");
        }
        
        if ($count > 1) {
            throw new \Exception("Role '{$this->name}' has multiple interface permissions. Only one is allowed.");
        }
        
        return true;
    }
}
