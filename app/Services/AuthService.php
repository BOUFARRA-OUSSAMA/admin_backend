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

class AuthService
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * AuthService constructor.
     * 
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
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
}
