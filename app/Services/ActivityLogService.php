<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Repositories\Interfaces\ActivityLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityLogService
{
    /**
     * @var ActivityLogRepositoryInterface
     */
    protected $activityLogRepository;

    /**
     * ActivityLogService constructor.
     * 
     * @param ActivityLogRepositoryInterface $activityLogRepository
     */
    public function __construct(ActivityLogRepositoryInterface $activityLogRepository)
    {
        $this->activityLogRepository = $activityLogRepository;
    }

    /**
     * Get filtered activity logs.
     * 
     * @param array $filters
     * @param string $sortBy
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFilteredLogs(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->activityLogRepository->getFilteredLogs($filters, $sortBy, $sortDirection, $perPage);
    }

    /**
     * Get logs for a specific user.
     * 
     * @param int $userId
     * @param string $sortBy
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserLogs(
        int $userId,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->activityLogRepository->getUserLogs($userId, $sortBy, $sortDirection, $perPage);
    }

    /**
     * Create a new activity log.
     * 
     * @param int $userId
     * @param string $action
     * @param string $module
     * @param string $description
     * @param string|null $entityType
     * @param int|null $entityId
     * @param string|null $ipAddress
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return ActivityLog
     */
    public function createLog(
        int $userId,
        string $action,
        string $module,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $ipAddress = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): ActivityLog {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $ipAddress,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
        ];

        return $this->activityLogRepository->createLog($data);
    }

    /**
     * Get logs filtered by action.
     * 
     * @param string $action
     * @param string $sortBy
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActionLogs(
        string $action,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->activityLogRepository->getActionLogs($action, $sortBy, $sortDirection, $perPage);
    }

    /**
     * Get logs filtered by module.
     * 
     * @param string $module
     * @param string $sortBy
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getModuleLogs(
        string $module,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->activityLogRepository->getModuleLogs($module, $sortBy, $sortDirection, $perPage);
    }

    /**
     * Get all available log actions.
     * 
     * @return array
     */
    public function getAllActions(): array
    {
        return $this->activityLogRepository->all()->pluck('action')->unique()->values()->toArray();
    }

    /**
     * Get all available log modules.
     * 
     * @return array
     */
    public function getAllModules(): array
    {
        return $this->activityLogRepository->all()->pluck('module')->unique()->values()->toArray();
    }
}
