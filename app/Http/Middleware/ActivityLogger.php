<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ActivityLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $action
     * @param  string  $module
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $action, $module)
    {
        $response = $next($request);

        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Log the activity
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'module' => $module,
                'description' => $this->generateDescription($request, $action, $module),
                'entity_type' => $this->getEntityType($request),
                'entity_id' => $this->getEntityId($request),
                'ip_address' => $request->ip()
            ]);
        } catch (\Exception $e) {
            // Fail silently, don't interrupt the request flow
            // But log the error somewhere if needed
        }

        return $response;
    }

    /**
     * Generate description based on request data
     */
    private function generateDescription(Request $request, $action, $module)
    {
        $method = $request->method();
        $path = $request->path();

        return ucfirst($action) . " " . $module . " via " . $method . " " . $path;
    }

    /**
     * Get entity type from request
     */
    private function getEntityType(Request $request)
    {
        $path = $request->path();
        $segments = explode('/', $path);

        if (count($segments) >= 2) {
            // Get singular form of the resource name
            $entityType = rtrim($segments[1], 's');
            return ucfirst($entityType);
        }

        return null;
    }

    /**
     * Get entity ID from request
     */
    private function getEntityId(Request $request)
    {
        $path = $request->path();
        $segments = explode('/', $path);

        if (count($segments) >= 3 && is_numeric($segments[2])) {
            return $segments[2];
        }

        return null;
    }
}
