<?php

namespace App\Services;

use App\Repositories\Interfaces\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionService
{
    /**
     * @var PermissionRepositoryInterface
     */
    protected $permissionRepository;

    /**
     * PermissionService constructor.
     * 
     * @param PermissionRepositoryInterface $permissionRepository
     */
    public function __construct(PermissionRepositoryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

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
    ): LengthAwarePaginator {
        return $this->permissionRepository->getFilteredPermissions($filters, $sortBy, $sortDirection, $perPage);
    }

    /**
     * Get permissions grouped by their category.
     * 
     * @return array
     */
    public function getPermissionsByGroup(): array
    {
        $groupedPermissions = $this->permissionRepository->getPermissionsByGroup();
        
        // Return directly as the collection is already grouped by key
        return $groupedPermissions->toArray();
    }

    /**
     * Get all groups available for permissions.
     * 
     * @return array
     */
    public function getAllGroups(): array
    {
        $groups = $this->permissionRepository->all()->pluck('group')->unique()->values();
        return $groups->toArray();
    }
}
