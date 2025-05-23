<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Protected routes
Route::group(['middleware' => ['jwt.auth']], function () {
    // Auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // User routes
    Route::get('users/counts-by-status', [UserController::class, 'countsByStatus']);
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/roles', [UserController::class, 'assignRoles']);
    Route::get('users/{user}/roles', [UserController::class, 'getUserRoles']);
    Route::get('users/{user}/permissions', [UserController::class, 'getUserPermissions']);
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('permission:users:reset-password');

    // Role routes
    Route::get('roles/check-name', [RoleController::class, 'checkNameExists']);
    Route::apiResource('roles', RoleController::class)->middleware([
        'index' => 'permission:roles:view',
        'show' => 'permission:roles:view',
        'store' => 'permission:roles:create',
        'update' => 'permission:roles:edit',
        'destroy' => 'permission:roles:delete',
    ]);
    Route::post('roles/{role}/permissions', [RoleController::class, 'assignPermissions'])
        ->middleware('permission:roles:assign-permissions');

    // Permission routes
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('permissions/groups', [PermissionController::class, 'getGroups']);
    Route::get('permissions/groups/list', [PermissionController::class, 'listGroups']);

    // Patient routes
    Route::apiResource('patients', PatientController::class);

    // Activity Log routes
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/users/{user}', [ActivityLogController::class, 'getUserLogs']);
    Route::get('activity-logs/actions/{action}', [ActivityLogController::class, 'getActionLogs']);
    Route::get('activity-logs/modules/{module}', [ActivityLogController::class, 'getModuleLogs']);
    Route::get('activity-logs/actions', [ActivityLogController::class, 'getActions']);
    Route::get('activity-logs/modules', [ActivityLogController::class, 'getModules']);

    // Analytics Routes (with permission middleware)
    Route::group(['middleware' => 'permission:analytics:view'], function () {
        // Existing routes
        Route::get('analytics/users', [AnalyticsController::class, 'getUserStats']);
        Route::get('analytics/roles', [AnalyticsController::class, 'getRoleStats']);
        Route::get('analytics/activities', [AnalyticsController::class, 'getActivityStats']);
        Route::get('analytics/user-activity', [AnalyticsController::class, 'getUserActivityStats']);
        Route::get('analytics/logins', [AnalyticsController::class, 'getLoginStats']);
        Route::get('analytics/user-registrations', [AnalyticsController::class, 'getUserRegistrations']); // Add this line
        // Security analytics route
        Route::get('analytics/export/{type}', [AnalyticsController::class, 'exportData']);
    });

    // AI Model Management routes will be added in future phases
});

// Test route in routes/api.php
Route::get('/test-middleware', function () {
    return response()->json(['success' => true, 'message' => 'Middleware is working']);
})->middleware('permission:analytics:view');

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Not Found',
        'errors' => ['endpoint' => 'The requested endpoint does not exist']
    ], 404);
});
