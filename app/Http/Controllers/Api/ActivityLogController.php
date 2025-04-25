<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        $query = ActivityLog::with('user');

        // Apply filters
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        if ($entityType = $request->query('entity_type')) {
            $query->where('entity_type', $entityType);
        }

        if ($entityId = $request->query('entity_id')) {
            $query->where('entity_id', $entityId);
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // Search in description
        if ($search = $request->query('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        // Get paginated results
        $logs = $query->orderBy($sortBy, $sortDirection)->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Activity logs retrieved successfully',
            'data' => [
                'items' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'last_page' => $logs->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Get activity logs for a specific user.
     *
     * @param  \App\Models\User  $user
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserLogs(User $user, Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        $logs = $user->activityLogs()
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'User activity logs retrieved successfully',
            'data' => [
                'items' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'last_page' => $logs->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Get logs filtered by action.
     *
     * @param  string  $action
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActionLogs($action, Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        $logs = ActivityLog::with('user')
            ->where('action', $action)
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Action logs retrieved successfully',
            'data' => [
                'items' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'last_page' => $logs->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Get logs filtered by module.
     *
     * @param  string  $module
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModuleLogs($module, Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        $logs = ActivityLog::with('user')
            ->where('module', $module)
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Module logs retrieved successfully',
            'data' => [
                'items' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'last_page' => $logs->lastPage()
                ]
            ]
        ]);
    }
}
