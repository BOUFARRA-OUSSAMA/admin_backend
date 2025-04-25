<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var PermissionService
     */
    protected $permissionService;

    /**
     * PermissionController constructor.
     *
     * @param PermissionService $permissionService
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of all permissions.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->query('search'),
            'group' => $request->query('group'),
        ];

        $perPage = $request->query('per_page', 50);
        $sortBy = $request->query('sort_by', 'group');
        $sortDirection = $request->query('sort_direction', 'asc');

        $permissions = $this->permissionService->getFilteredPermissions(
            $filters,
            $sortBy,
            $sortDirection,
            $perPage
        );

        return $this->paginated($permissions, 'Permissions retrieved successfully');
    }

    /**
     * Get permissions grouped by their group.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroups()
    {
        $groupedPermissions = $this->permissionService->getPermissionsByGroup();

        return $this->success($groupedPermissions, 'Permission groups retrieved successfully');
    }

    /**
     * List all available permission groups.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listGroups()
    {
        $groups = $this->permissionService->getAllGroups();

        return $this->success($groups, 'Permission groups retrieved successfully');
    }
}
