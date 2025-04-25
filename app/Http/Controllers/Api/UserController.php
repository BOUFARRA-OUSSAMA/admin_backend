<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\AssignRolesRequest;
use App\Models\User;
use App\Services\UserService;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     * @param AuthService $authService
     */
    public function __construct(UserService $userService, AuthService $authService)
    {
        $this->userService = $userService;
        $this->authService = $authService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'role_id' => $request->query('role_id'),
        ];

        $perPage = $request->query('per_page', 15);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        $users = $this->userService->getFilteredUsers($filters, $sortBy, $sortDirection, $perPage);

        return $this->paginated($users, 'Users retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\User\StoreUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        // Log the activity
        try {
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->userService->logUserActivity(
                $authUser->id,
                'create',
                'Created user: ' . $user->name,
                $user,
                $request
            );
        } catch (\Exception $e) {
            // Just continue if logging fails
        }

        return $this->success($user->load('roles'), 'User created successfully', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        $user = $this->userService->getUserById($user->id);

        return $this->success($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\User\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $oldUser = $user->toArray();

        $updatedUser = $this->userService->updateUser($user->id, $request->validated());

        // Log the activity
        try {
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->userService->logUserActivity(
                $authUser->id,
                'update',
                'Updated user: ' . $updatedUser->name,
                $updatedUser,
                $request,
                $oldUser,
                $updatedUser->toArray()
            );
        } catch (\Exception $e) {
            // Just continue if logging fails
        }

        return $this->success($updatedUser, 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user, Request $request)
    {
        try {
            $authUser = JWTAuth::parseToken()->authenticate();

            // Delete user
            $result = $this->userService->deleteUser($user->id, $authUser->id);

            if (!$result) {
                return $this->error('You cannot delete your own account', 403);
            }

            // Log the activity
            $this->userService->logUserActivity(
                $authUser->id,
                'delete',
                'Deleted user: ' . $user->name,
                $user,
                $request
            );

            return $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign roles to a user
     *
     * @param  \App\Http\Requests\User\AssignRolesRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignRoles(AssignRolesRequest $request, User $user)
    {
        $oldRoles = $user->roles()->pluck('id')->toArray();

        // Access the validated data directly
        $validatedData = $request->validated();
        $roles = $validatedData['roles'];

        $updatedUser = $this->userService->assignRoles($user->id, $roles);

        // Log the activity
        try {
            $authUser = JWTAuth::parseToken()->authenticate();
            $this->userService->logUserActivity(
                $authUser->id,
                'assign',
                'Assigned roles to user: ' . $user->name,
                $user,
                $request,
                ['roles' => $oldRoles],
                ['roles' => $roles]
            );
        } catch (\Exception $e) {
            // Just continue if logging fails
        }

        return $this->success($updatedUser, 'Roles assigned successfully');
    }

    /**
     * Get user roles
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserRoles(User $user)
    {
        $roles = $this->userService->getUserRoles($user->id);

        return $this->success($roles);
    }

    /**
     * Get user permissions
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPermissions(User $user)
    {
        $permissions = $this->userService->getUserPermissions($user->id);

        return $this->success($permissions);
    }
}
