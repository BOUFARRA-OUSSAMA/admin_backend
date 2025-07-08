<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\ActivityLog;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    protected $interfacePermissionService;

    /**
     * AuthService constructor.
     * 
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository, InterfacePermissionService $interfacePermissionService)
    {
        $this->userRepository = $userRepository;
        $this->interfacePermissionService = $interfacePermissionService;
    }

    /**
     * Attempt to login a user.
     * 
     * @param array $credentials
     * @return array|bool
     * @throws JWTException
     */
    public function login(array $credentials)
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            return false;
        }

        $user = JWTAuth::user();

        if ($user->status !== 'active') {
            JWTAuth::invalidate(JWTAuth::getToken());
            return false;
        }

        return [
            'token' => $token,
            'user' => $user
        ];
    }

    /**
     * Register a new user.
     * 
     * @param array $userData
     * @return User
     */
    public function register(array $userData): User
    {
        $userData['password'] = Hash::make($userData['password']);
        $userData['status'] = 'pending'; // Default status for new users

        $user = $this->userRepository->create($userData);

        // Attach default guest role if available
        $guestRole = Role::where('code', 'guest')->first();
        if ($guestRole) {
            $user->roles()->attach($guestRole->id);
        }

        return $user;
    }

    /**
     * Log the current user out.
     * 
     * @return bool
     * @throws JWTException
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return true;
    }

    /**
     * Refresh the current JWT token.
     * 
     * @return string|bool New token or false on failure
     * @throws JWTException|TokenExpiredException|TokenInvalidException
     */
    public function refreshToken()
    {
        return JWTAuth::parseToken()->refresh();
    }

    /**
     * Get the currently authenticated user.
     * 
     * @return User|null
     * @throws JWTException|TokenExpiredException|TokenInvalidException
     */
    public function getAuthenticatedUser()
    {
        return JWTAuth::parseToken()->authenticate();
    }

    /**
     * Log activity helper method
     *
     * @param int $userId
     * @param string $action
     * @param string $module
     * @param string $description
     * @param string|null $entityType
     * @param int|null $entityId
     * @param string|null $ipAddress
     * @return void
     */
    public function logActivity($userId, $action, $module, $description, $entityType = null, $entityId = null, $ipAddress = null)
    {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Format token response
     * 
     * @param string $token
     * @return array
     */
    public function respondWithTokenData($token)
    {
        $user = JWTAuth::user();
        $user->load('roles.permissions');

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user
        ];
    }

    /**
     * Generate JWT token with enhanced permission data.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    public function generateToken(User $user)
    {
        // Get all user permissions through roles
        $permissions = collect();
        $permissionsByModule = [];
        
        foreach ($user->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }
        
        // Create a flat array of permission codes
        $permissionCodes = $permissions->pluck('code')->unique()->values()->toArray();
        
        // Group permissions by module for efficient checking
        foreach ($permissions as $permission) {
            $parts = explode(':', $permission->code);
            $module = $parts[0];
            
            if (!isset($permissionsByModule[$module])) {
                $permissionsByModule[$module] = [];
            }
            
            $permissionsByModule[$module][] = $permission->code;
        }
        
        // Get interface permission
        $interfacePermission = $this->interfacePermissionService->getUserInterfacePermission($user);
        $interfaceName = $interfacePermission ? $interfacePermission->getInterfaceName() : null;
        
        // Create token with custom claims
        $token = Auth::claims([
            'permissions' => $permissionCodes,
            'permission_map' => $permissionsByModule,
            'interface' => $interfaceName,
        ])->login($user);
        
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles,
                'permissions' => $permissionCodes,
                'interface' => $interfaceName,
            ]
        ];
    }

    /**
     * Attempt to authenticate a user and generate token.
     *
     * @param  string  $email
     * @param  string  $password
     * @return array|bool
     */
    public function attempt($email, $password)
    {
        if ($token = Auth::attempt(['email' => $email, 'password' => $password])) {
            $user = Auth::user();
            return $this->generateToken($user);
        }
        
        return false;
    }
}
