<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * UserService constructor.
     * 
     * @param UserRepositoryInterface $userRepository
     * @param AuthService $authService
     */
    public function __construct(UserRepositoryInterface $userRepository, AuthService $authService)
    {
        $this->userRepository = $userRepository;
        $this->authService = $authService;
    }

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
    ): LengthAwarePaginator {
        return $this->userRepository->getFilteredUsers($filters, $sortBy, $sortDirection, $perPage);
    }

    /**
     * Get user by ID.
     * 
     * @param int $userId
     * @return User
     */
    public function getUserById(int $userId): User
    {
        return $this->userRepository->findById($userId, ['*'], ['roles.permissions', 'aiModels']);
    }

    /**
     * Create a new user.
     * 
     * @param array $userData
     * @return User
     */
    public function createUser(array $userData): User
    {
        // Hash the password
        $userData['password'] = Hash::make($userData['password']);

        // Create the user
        $user = $this->userRepository->create($userData);

        // Assign roles if provided
        if (isset($userData['roles'])) {
            $this->userRepository->assignRoles($user->id, $userData['roles']);
        }

        return $user->load('roles');
    }

    /**
     * Update user details.
     * 
     * @param int $userId
     * @param array $userData
     * @return User
     */
    public function updateUser(int $userId, array $userData): User
    {
        // Get the user
        $user = $this->userRepository->findById($userId);

        // Store old values for logging
        $oldValues = $user->toArray();

        // Handle password separately
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }

        // Update user
        $this->userRepository->update($userId, $userData);

        // Reload user
        $user = $this->userRepository->findById($userId);

        return $user->load('roles');
    }

    /**
     * Delete a user.
     * 
     * @param int $userId
     * @param int $currentUserId
     * @return bool
     */
    public function deleteUser(int $userId, int $currentUserId): bool
    {
        // Don't allow deleting yourself
        if ($userId === $currentUserId) {
            return false;
        }

        return $this->userRepository->deleteById($userId);
    }

    /**
     * Assign roles to a user.
     * 
     * @param int $userId
     * @param array $roleIds
     * @return User
     */
    public function assignRoles(int $userId, array $roleIds): User
    {
        $this->userRepository->assignRoles($userId, $roleIds);
        return $this->getUserById($userId);
    }

    /**
     * Change user status.
     * 
     * @param int $userId
     * @param string $status
     * @return bool
     */
    public function changeStatus(int $userId, string $status): bool
    {
        return $this->userRepository->changeStatus($userId, $status);
    }

    /**
     * Get user roles.
     * 
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserRoles(int $userId)
    {
        return $this->getUserById($userId)->roles;
    }

    /**
     * Get user permissions.
     * 
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserPermissions(int $userId)
    {
        return $this->userRepository->getUserPermissions($userId);
    }

    /**
     * Log user related activity.
     * 
     * @param int $userId
     * @param string $action
     * @param string $description
     * @param User $targetUser
     * @param Request $request
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    public function logUserActivity(
        int $userId,
        string $action,
        string $description,
        User $targetUser,
        Request $request,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $this->authService->logActivity(
            $userId,
            $action,
            'Users',
            $description,
            'User',
            $targetUser->id,
            $request->ip(),
            $oldValues,
            $newValues
        );
    }

    /**
     * Get user counts by status.
     *
     * @return array
     */
    public function getUserCountsByStatus(): array
    {
        return $this->userRepository->getUserCountsByStatus();
    }
}
