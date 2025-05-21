<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignPermissionsRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var RoleService
     */
    protected $roleService;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * RoleController constructor.
     *
     * @param RoleService $roleService
     * @param AuthService $authService
     */
    public function __construct(RoleService $roleService, AuthService $authService)
    {
        $this->roleService = $roleService;
        $this->authService = $authService;
    }

    /**
     * Display a listing of the roles with search capability.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $sortBy = $request->query('sort_by', 'name');
        $sortDirection = $request->query('sort_direction', 'asc');
        $search = $request->query('search');

        $roles = $this->getAllRoles($perPage, $sortBy, $sortDirection, $search);

        return $this->paginated($roles, 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role in storage.
     *
     * @param  \App\Http\Requests\Role\StoreRoleRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRoleRequest $request)
    {
        $role = $this->roleService->createRole($request->validated());

        // Log the activity
        $user = $this->authService->getAuthenticatedUser();
        $this->authService->logActivity(
            $user->id,
            'create',
            'Roles',
            'Created role: ' . $role->name,
            'Role',
            $role->id,
            request()->ip()
        );

        return $this->success($role, 'Role created successfully', 201);
    }

    /**
     * Display the specified role.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Role $role)
    {
        $role = $this->roleService->getRoleById($role->id);

        return $this->success($role);
    }

    /**
     * Update the specified role in storage.
     *
     * @param  \App\Http\Requests\Role\UpdateRoleRequest  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $updatedRole = $this->roleService->updateRole($role->id, $request->validated());

        // Log the activity
        $user = $this->authService->getAuthenticatedUser();
        $this->authService->logActivity(
            $user->id,
            'update',
            'Roles',
            'Updated role: ' . $updatedRole->name,
            'Role',
            $updatedRole->id,
            request()->ip()
        );

        return $this->success($updatedRole, 'Role updated successfully');
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  \App\Models\Role  $role
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Role $role, Request $request)
    {
        $result = $this->roleService->deleteRole($role->id);

        if (!$result) {
            return $this->error('Cannot delete system role.', 403);
        }

        // Log the activity
        $user = $this->authService->getAuthenticatedUser();
        $this->authService->logActivity(
            $user->id,
            'delete',
            'Roles',
            'Deleted role: ' . $role->name,
            'Role',
            $role->id,
            request()->ip()
        );

        return $this->success(null, 'Role deleted successfully');
    }

    /**
     * Assign permissions to a role.
     *
     * @param  \App\Http\Requests\Role\AssignPermissionsRequest  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignPermissions(AssignPermissionsRequest $request, Role $role)
    {
        $updatedRole = $this->roleService->assignPermissions($role->id, $request->validated()['permissions']);

        // Log the activity
        $user = $this->authService->getAuthenticatedUser();
        $this->authService->logActivity(
            $user->id,
            'assign',
            'Roles',
            'Assigned permissions to role: ' . $role->name,
            'Role',
            $role->id,
            request()->ip()
        );

        return $this->success($updatedRole, 'Permissions assigned successfully');
    }

    /**
     * Get all roles with pagination and search
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
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }
}
