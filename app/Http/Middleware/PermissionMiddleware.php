<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        try {
            // Get the authenticated user using JWTAuth
            $user = JWTAuth::parseToken()->authenticate();
            
            // Admin role has all permissions automatically
            if ($user->roles->where('code', 'admin')->count() > 0) {
                return $next($request);
            }
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => ['auth' => 'User not authenticated']
                ], 401);
            }

            // Check if user has the required permission through any of their roles
            $hasPermission = false;
            foreach ($user->roles as $role) {
                foreach ($role->permissions as $perm) {
                    if ($perm->code === $permission) {
                        $hasPermission = true;
                        break 2;
                    }
                }
            }

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'errors' => ['permission' => 'You do not have the required permission: ' . $permission]
                ], 403);
            }

            return $next($request);
            
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired',
                'errors' => ['token' => $e->getMessage()]
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid',
                'errors' => ['token' => $e->getMessage()]
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token absent',
                'errors' => ['token' => $e->getMessage()]
            ], 401);
        }
    }
}