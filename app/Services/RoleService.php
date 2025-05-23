<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RoleService
{
    /**
     * Get all roles with pagination
     * 
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDirection
     * @param string|null $search
     * @return LengthAwarePaginator
     */
    public function getAllRoles(int $perPage = 15, string $sortBy = 'name', string $sortDirection = 'asc', ?string $search = null): LengthAwarePaginator
    {
        $query = Role::query()->with('permissions')->withCount('permissions');

        // Apply search if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Get role by ID
     * 
     * @param int $id
     * @return Role
     */
    public function getRoleById(int $id): Role
    {
        return Role::with('permissions')->findOrFail($id);
    }

    /**
     * Create a new role
     * 
     * @param array $data
     * @return Role
     */
    public function createRole(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
        ]);

        // Attach permissions if provided
        if (isset($data['permissions'])) {
            $role->permissions()->attach($data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Update a role
     * 
     * @param int $id
     * @param array $data
     * @return Role
     */
    public function updateRole(int $id, array $data): Role
    {
        $role = $this->getRoleById($id);

        if (isset($data['name'])) {
            $role->name = $data['name'];
        }

        if (isset($data['code'])) {
            $role->code = $data['code'];
        }

        if (isset($data['description'])) {
            $role->description = $data['description'];
        }

        $role->save();

        // Add this section to handle permissions
        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Delete a role
     * 
     * @param int $id
     * @return bool
     */
    public function deleteRole(int $id): bool
    {
        $role = $this->getRoleById($id);

        // Check if role is protected
        $protectedRoles = ['admin', 'patient', 'doctor', 'guest'];

        if (in_array($role->code, $protectedRoles)) {
            return false;
        }

        // Detach all permissions and users before deletion
        $role->permissions()->detach();
        $role->users()->detach();

        return $role->delete();
    }

    /**
     * Assign permissions to a role
     * 
     * @param int $roleId
     * @param array $permissionIds
     * @return Role
     */
    public function assignPermissions(int $roleId, array $permissionIds): Role
    {
        $role = $this->getRoleById($roleId);
        $role->permissions()->sync($permissionIds);

        return $role->load('permissions');
    }
}
