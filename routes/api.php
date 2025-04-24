<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\PatientController;

// Authentication Routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::post('patients', [PatientController::class, 'store']);
// Protected Routes
Route::group(['middleware' => ['jwt.auth']], function () {
    // User Routes

    Route::get('patients', [PatientController::class, 'index']);
    Route::get('patients/{id}', [PatientController::class, 'show']);

    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/roles', [UserController::class, 'assignRoles']);
    Route::get('users/{user}/roles', [UserController::class, 'getUserRoles']);
    Route::get('users/{user}/permissions', [UserController::class, 'getUserPermissions']);

    // Role Routes
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{role}/permissions', [RoleController::class, 'assignPermissions']);

    // Permission Routes
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('permissions/groups', [PermissionController::class, 'getGroups']);

    // Logs Routes (with permission middleware)
    Route::group(['middleware' => ['permission:logs:view']], function () {
        Route::get('logs', [ActivityLogController::class, 'index']);
        Route::get('logs/user/{user}', [ActivityLogController::class, 'getUserLogs']);
        Route::get('logs/action/{action}', [ActivityLogController::class, 'getActionLogs']);
        Route::get('logs/module/{module}', [ActivityLogController::class, 'getModuleLogs']);

    
    });

    // Analytics Routes (with permission middleware)
    Route::group(['middleware' => ['permission:analytics:view']], function () {
        Route::get('analytics/users', [AnalyticsController::class, 'getUserStats']);
        Route::get('analytics/roles', [AnalyticsController::class, 'getRoleStats']);
        Route::get('analytics/activities', [AnalyticsController::class, 'getActivityStats']);
        Route::get('analytics/logins', [AnalyticsController::class, 'getLoginStats']);
        Route::get('analytics/export/{type}', [AnalyticsController::class, 'exportData']);
    });
});
