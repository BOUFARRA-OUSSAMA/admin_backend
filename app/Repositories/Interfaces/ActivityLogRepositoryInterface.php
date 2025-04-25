<?php

namespace App\Repositories\Interfaces;

use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;

interface ActivityLogRepositoryInterface extends EloquentRepositoryInterface
{
    /**
     * Get filtered activity logs with pagination.
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
    ): LengthAwarePaginator;

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
    ): LengthAwarePaginator;

    /**
     * Create a new activity log.
     * 
     * @param array $data
     * @return ActivityLog
     */
    public function createLog(array $data): ActivityLog;

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
    ): LengthAwarePaginator;

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
    ): LengthAwarePaginator;
}
