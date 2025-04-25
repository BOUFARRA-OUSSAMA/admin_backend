<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
     * Permission belongs to many roles relationship
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Get permissions grouped by their group attribute
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getByGroup()
    {
        $permissions = self::all();
        return $permissions->groupBy('group');
    }
}
