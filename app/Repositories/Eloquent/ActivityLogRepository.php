<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityLog;
use App\Repositories\Interfaces\ActivityLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityLogRepository extends BaseRepository implements ActivityLogRepositoryInterface
{
    /**
     * ActivityLogRepository constructor.
     * 
     * @param ActivityLog $model
     */
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @inheritDoc
     */
    public function getFilteredLogs(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->model->with('user');

        // Apply filters
        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action']) && $filters['action']) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['module']) && $filters['module']) {
            $query->where('module', $filters['module']);
        }

        if (isset($filters['entity_type']) && $filters['entity_type']) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['entity_id']) && $filters['entity_id']) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (isset($filters['from_date']) && $filters['from_date']) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date']) && $filters['to_date']) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Search in description
        if (isset($filters['search']) && $filters['search']) {
            $query->where('description', 'like', "%{$filters['search']}%");
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function getUserLogs(
        int $userId,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->model->where('user_id', $userId)
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function createLog(array $data): ActivityLog
    {
        return $this->model->create($data);
    }

    /**
     * @inheritDoc
     */
    public function getActionLogs(
        string $action,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->model->with('user')
            ->where('action', $action)
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function getModuleLogs(
        string $module,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->model->with('user')
            ->where('module', $module)
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }
}
