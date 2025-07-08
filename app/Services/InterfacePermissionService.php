<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class InterfacePermissionService
{
    /**
     * Get all available interface permissions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllInterfacePermissions()
    {
        return Permission::interfaces()->get();
    }

    /**
     * Get the interface permission for a user.
     *
     * @param  \App\Models\User  $user
     * @return \App\Models\Permission|null
     */
    public function getUserInterfacePermission(User $user)
    {
        // Get all permissions for the user through their roles
        $permissions = collect();
        
        foreach ($user->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }
        
        // Filter to interface permissions only
        $interfacePermissions = $permissions->filter(function ($permission) {
            return $permission->isInterfacePermission();
        });
        
        // Return the first interface permission (should be only one)
        return $interfacePermissions->first();
    }

    /**
     * Get the interface name for a user (e.g., 'admin', 'doctor').
     *
     * @param  \App\Models\User  $user
     * @return string|null
     */
    public function getUserInterfaceName(User $user)
    {
        $permission = $this->getUserInterfacePermission($user);
        return $permission ? $permission->getInterfaceName() : null;
    }

    /**
     * Validate that a user has exactly one interface permission.
     *
     * @param  \App\Models\User  $user
     * @return bool
     * @throws \Exception
     */
    public function validateUserInterface(User $user)
    {
        // Get all permissions for the user through their roles
        $permissions = collect();
        
        foreach ($user->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }
        
        // Filter to interface permissions only
        $interfacePermissions = $permissions->filter(function ($permission) {
            return $permission->isInterfacePermission();
        });
        
        $count = $interfacePermissions->count();
        
        if ($count === 0) {
            throw new \Exception("User '{$user->name}' must have access to exactly one interface.");
        }
        
        if ($count > 1) {
            throw new \Exception("User '{$user->name}' has access to multiple interfaces. Only one is allowed.");
        }
        
        return true;
    }

    /**
     * Check if assigning a permission to a role would violate the single interface rule.
     *
     * @param  \App\Models\Role  $role
     * @param  \App\Models\Permission  $permission
     * @return bool
     */
    public function wouldViolateInterfaceRule(Role $role, Permission $permission)
    {
        // If the permission is not an interface permission, it can't violate the rule
        if (!$permission->isInterfacePermission()) {
            return false;
        }
        
        // Get existing interface permissions for this role
        $existingInterfacePermissions = $role->interfacePermissions();
        
        // If no existing interface permissions, this one is fine
        if ($existingInterfacePermissions->count() === 0) {
            return false;
        }
        
        // If there's already an interface permission and it's different from this one,
        // adding this permission would violate the rule
        $existingPermission = $existingInterfacePermissions->first();
        return $existingPermission->id !== $permission->id;
    }
}