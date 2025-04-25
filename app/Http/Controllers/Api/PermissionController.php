<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of all permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 50);
        $sortBy = $request->query('sort_by', 'group');
        $sortDirection = $request->query('sort_direction', 'asc');

        $query = Permission::query();

        // Apply search if provided
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by group if provided
        if ($group = $request->query('group')) {
            $query->where('group', $group);
        }

        // Get paginated results
        $permissions = $query->orderBy($sortBy, $sortDirection)->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => [
                'items' => $permissions->items(),
                'pagination' => [
                    'total' => $permissions->total(),
                    'current_page' => $permissions->currentPage(),
                    'per_page' => $permissions->perPage(),
                    'last_page' => $permissions->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Get permissions grouped by their group.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroups()
    {
        $groupedPermissions = Permission::getByGroup();

        $result = [];
        foreach ($groupedPermissions as $group => $permissions) {
            $result[] = [
                'group' => $group,
                'permissions' => $permissions
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission groups retrieved successfully',
            'data' => $result
        ]);
    }
}
