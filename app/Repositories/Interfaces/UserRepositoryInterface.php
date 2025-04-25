<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends EloquentRepositoryInterface
{
    /**
     * Find user by email.
     * 
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get users by role.
     * 
     * @param string $roleCode
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsersByRole(string $roleCode, int $perPage = 15): LengthAwarePaginator;

    /**
     * Assign roles to user.
     * 
     * @param int $userId
     * @param array $roleIds
     * @return bool
     */
    public function assignRoles(int $userId, array $roleIds): bool;

    /**
     * Get all permissions for a user.
     * 
     * @param int $userId
     * @return Collection
     */
    public function getUserPermissions(int $userId): Collection;

    /**
     * Get filtered users with pagination.
     * 
     * @param array $filters
     * @param string $sortBy
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFilteredUsers(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Change user status.
     * 
     * @param int $userId
     * @param string $status
     * @return bool
     */
    public function changeStatus(int $userId, string $status): bool;
}
