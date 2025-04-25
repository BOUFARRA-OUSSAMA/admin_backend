<?php

namespace App\Repositories\Interfaces;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PermissionRepositoryInterface extends EloquentRepositoryInterface
{
    /**
     * Get filtered permissions with pagination.
     * 
     * @param array $filters
     * @param string $sortBy
     * @param string $sortDirection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFilteredPermissions(
        array $filters = [],
        string $sortBy = 'group',
        string $sortDirection = 'asc',
        int $perPage = 50
    ): LengthAwarePaginator;

    /**
     * Get permissions grouped by their group attribute.
     * 
     * @return Collection
     */
    public function getPermissionsByGroup(): Collection;

    /**
     * Find permission by code.
     * 
     * @param string $code
     * @return Permission|null
     */
    public function findByCode(string $code): ?Permission;
}
