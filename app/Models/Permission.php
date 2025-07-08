<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'group',
        'description',
    ];

    /**
     * Define relationship with roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    /**
     * Scope a query to only include interface permissions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInterfaces($query)
    {
        return $query->where('group', 'interfaces');
    }

    /**
     * Check if this permission is an interface permission.
     *
     * @return bool
     */
    public function isInterfacePermission()
    {
        return $this->group === 'interfaces' && strpos($this->code, 'interfaces:') === 0;
    }

    /**
     * Get the interface name from this permission (e.g., 'admin', 'doctor').
     * Only works for interface permissions.
     *
     * @return string|null
     */
    public function getInterfaceName()
    {
        if (!$this->isInterfacePermission()) {
            return null;
        }
        
        // Extract the part after 'interfaces:'
        $parts = explode(':', $this->code);
        if (count($parts) < 2) {
            return null;
        }
        
        // Extract the interface name (e.g., 'admin_access' -> 'admin')
        $interfaceName = str_replace('_access', '', $parts[1]);
        return $interfaceName;
    }

    /**
     * Get the interface route prefix for this permission.
     *
     * @return string|null
     */
    public function getInterfaceRoutePrefix()
    {
        $interfaceName = $this->getInterfaceName();
        return $interfaceName ? "/{$interfaceName}" : null;
    }
}
