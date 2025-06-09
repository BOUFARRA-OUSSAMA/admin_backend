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
     * Role belongs to many users relationship
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withTimestamps();
    }

    /**
     * Role belongs to many permissions relationship
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Check if role has specific permission
     * 
     * @param string|\App\Models\Permission $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions->where('code', $permission)->count() > 0;
        }

        // Convert array to collection if needed
        if (is_array($permission)) {
            $permission = collect($permission);
        }

        return $permission->intersect($this->permissions->pluck('code'))->count() > 0;
    }
}
