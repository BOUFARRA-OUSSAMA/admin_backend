<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\Role;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * UserRepository constructor.
     * 
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * @inheritDoc
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * @inheritDoc
     */
    public function getUsersByRole(string $roleCode, int $perPage = 15): LengthAwarePaginator
    {
        $role = Role::where('code', $roleCode)->first();

        if (!$role) {
            return $this->model->newQuery()->where('id', 0)->paginate($perPage); // Return empty paginator
        }

        return $role->users()->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function assignRoles(int $userId, array $roleIds): bool
    {
        $user = $this->findById($userId);
        $user->roles()->sync($roleIds);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getUserPermissions(int $userId): Collection
    {
        $user = $this->findById($userId);
        return $user->getAllPermissions();
    }

    /**
     * @inheritDoc
     */
    public function getFilteredUsers(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->query()->with('roles');

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['role_id']) && $filters['role_id']) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('roles.id', $filters['role_id']);
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function changeStatus(int $userId, string $status): bool
    {
        $user = $this->findById($userId);
        $user->status = $status;
        return $user->save();
    }


    /**
     * Get count of users grouped by status.
     *
     * @return array
     */
    public function getUserCountsByStatus(): array
    {
        return $this->model->query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
