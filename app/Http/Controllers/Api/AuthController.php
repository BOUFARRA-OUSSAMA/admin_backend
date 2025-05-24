<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use App\Services\JwtTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\ActivityLog;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * @var JwtTokenService
     */
    protected $jwtTokenService;

    /**
     * AuthController constructor.
     * 
     * @param AuthService $authService
     * @param JwtTokenService $jwtTokenService
     */
    public function __construct(AuthService $authService, JwtTokenService $jwtTokenService)
    {
        $this->authService = $authService;
        $this->jwtTokenService = $jwtTokenService;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only(['email', 'password']);
        $email = $request->input('email');

        // Try to find the user by email to get their ID for logging
        $user = User::where('email', $email)->first();
        $userId = $user ? $user->id : null;

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                // Log the failed login attempt
                $this->authService->logActivity(
                    $userId,
                    'failed_login',
                    'Authentication',
                    'Failed login attempt with email: ' . $email,
                    null,
                    null,
                    $request->ip()
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if user is active
            $user = JWTAuth::user();
            if ($user->status !== 'active') {
                // Log the inactive account login attempt
                $this->authService->logActivity(
                    $user->id,
                    'failed_login',
                    'Authentication',
                    'Login attempt to inactive account',
                    null,
                    null,
                    $request->ip()
                );

                JWTAuth::invalidate(JWTAuth::getToken());
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive. Please contact the administrator.'
                ], 403);
            }

            // Update last_login_at timestamp
            $user->last_login_at = now();
            $user->save();

            // Record the token after successful authentication
            $this->jwtTokenService->recordToken($user->id, $token);

            // Log the login activity
            $this->authService->logActivity(
                $user->id,
                'login',
                'Authentication',
                'User logged in',
                null,
                null,
                $request->ip()
            );

            return $this->respondWithToken($token);
        } catch (JWTException $e) {
            // Log the exception
            $this->authService->logActivity(
                $userId,
                'failed_login',
                'Authentication',
                'Login error: ' . $e->getMessage(),
                null,
                null,
                $request->ip()
            );

            return response()->json([
                'success' => false,
                'message' => 'Could not create token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone ?? null,
            'status' => 'pending', // Default status
        ]);

        // Attach default guest role if available
        $guestRole = Role::where('code', 'guest')->first();
        if ($guestRole) {
            $user->roles()->attach($guestRole->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'data' => $user
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Log the logout activity
            $this->authService->logActivity(
                $user->id,
                'logout',
                'Authentication',
                'User logged out',
                null,
                null,
                $request->ip()
            );

            // Revoke the token
            $this->jwtTokenService->revokeToken(JWTAuth::getToken()->get());

            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $user->load('roles.permissions');

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired',
                'error' => $e->getMessage()
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid',
                'error' => $e->getMessage()
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token absent',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $oldToken = JWTAuth::getToken();
            $token = JWTAuth::refresh($oldToken);
            
            // Revoke old token and record new one
            $this->jwtTokenService->revokeToken($oldToken->get());
            $this->jwtTokenService->recordToken(JWTAuth::setToken($token)->authenticate()->id, $token);
            
            return $this->respondWithToken($token);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired and can no longer be refreshed',
                'error' => $e->getMessage()
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        // Get the user directly from JWTAuth
        $user = JWTAuth::user();
        $user->load('roles.permissions');

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user
        ]);
    }
}