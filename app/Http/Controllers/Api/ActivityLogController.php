<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var ActivityLogService
     */
    protected $activityLogService;

    /**
     * ActivityLogController constructor.
     *
     * @param ActivityLogService $activityLogService
     */
    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display a listing of activity logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = [
            'user_id' => $request->query('user_id'),
            'action' => $request->query('action'),
            'module' => $request->query('module'),
            'entity_type' => $request->query('entity_type'),
            'entity_id' => $request->query('entity_id'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'search' => $request->query('search'),
        ];

        $perPage = $request->query('per_page', 20);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        $logs = $this->activityLogService->getFilteredLogs($filters, $sortBy, $sortDirection, $perPage);

        return $this->paginated($logs, 'Activity logs retrieved successfully');
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

        $logs = $this->activityLogService->getUserLogs($user->id, $sortBy, $sortDirection, $perPage);

        return $this->paginated($logs, 'User activity logs retrieved successfully');
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

        $logs = $this->activityLogService->getActionLogs($action, $sortBy, $sortDirection, $perPage);

        return $this->paginated($logs, 'Action logs retrieved successfully');
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

        $logs = $this->activityLogService->getModuleLogs($module, $sortBy, $sortDirection, $perPage);

        return $this->paginated($logs, 'Module logs retrieved successfully');
    }

    /**
     * Get all available log actions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActions()
    {
        $actions = $this->activityLogService->getAllActions();

        return $this->success($actions, 'Log actions retrieved successfully');
    }

    /**
     * Get all available log modules.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModules()
    {
        $modules = $this->activityLogService->getAllModules();

        return $this->success($modules, 'Log modules retrieved successfully');
    }
}
