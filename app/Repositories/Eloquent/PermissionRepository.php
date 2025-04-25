<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    /**
     * PermissionRepository constructor.
     * 
     * @param Permission $model
     */
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    /**
     * @inheritDoc
     */
    public function getFilteredPermissions(
        array $filters = [],
        string $sortBy = 'group',
        string $sortDirection = 'asc',
        int $perPage = 50
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Apply search filter
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply group filter
        if (isset($filters['group']) && $filters['group']) {
            $query->where('group', $filters['group']);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function getPermissionsByGroup(): Collection
    {
        $permissions = $this->all();
        return $permissions->groupBy('group');
    }

    /**
     * @inheritDoc
     */
    public function findByCode(string $code): ?Permission
    {
        return $this->model->where('code', $code)->first();
    }
}
