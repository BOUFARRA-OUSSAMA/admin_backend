<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AiDiagnosticController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\PatientAppointmentController;
use App\Http\Controllers\Api\DoctorAppointmentController;
use App\Http\Controllers\Api\PersonalInfoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
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
        Route::get('analytics/user-registrations', [AnalyticsController::class, 'getUserRegistrations']);
        // Security analytics route
        Route::get('analytics/export/{type}', [AnalyticsController::class, 'exportData']);
        Route::get('analytics/active-sessions', [AnalyticsController::class, 'getCurrentActiveSessions']);

        // New financial analytics routes
        Route::get('analytics/revenue', [AnalyticsController::class, 'getRevenueAnalytics']);
        Route::get('analytics/services', [AnalyticsController::class, 'getServiceAnalytics']);
        Route::get('analytics/doctor-revenue', [AnalyticsController::class, 'getDoctorRevenueAnalytics']);

        // Current period revenue endpoints
        Route::get('analytics/revenue/week/current', [AnalyticsController::class, 'getCurrentWeekRevenue']);
        Route::get('analytics/revenue/month/current', [AnalyticsController::class, 'getCurrentMonthRevenue']);
        Route::get('analytics/revenue/year/current', [AnalyticsController::class, 'getCurrentYearRevenue']);
    });

    // Bill routes for receptionists/staff - REMOVE duplicate jwt.auth middleware
    Route::group(['middleware' => 'permission:bills:manage'], function () {
        // Move this specific route BEFORE the resource route
        Route::get('bills/by-patient/{patient}', [BillController::class, 'getPatientBills']);
        
        // Then the resource route
        Route::get('bills', [BillController::class, 'index']);
        Route::post('bills', [BillController::class, 'store']);
        Route::get('bills/{bill}', [BillController::class, 'show']);
        Route::put('bills/{bill}', [BillController::class, 'update']);
        Route::delete('bills/{bill}', [BillController::class, 'destroy']);
        
        // Other bill routes can remain after
        Route::get('bills/{bill}/items', [BillController::class, 'getItems']);
        Route::post('bills/{bill}/items', [BillController::class, 'addItem']);
        Route::put('bills/{bill}/items/{item}', [BillController::class, 'updateItem']);
        Route::delete('bills/{bill}/items/{item}', [BillController::class, 'removeItem']);
        Route::get('bills/{bill}/pdf', [BillController::class, 'downloadPdf'])->name('bills.pdf.download');
    });

    // Bill routes for patients (read-only) - REMOVE extra middleware group
    Route::get('patient/bills', [BillController::class, 'getMyBills']);
    Route::get('patient/bills/{bill}', [BillController::class, 'viewBill']);

    // APPOINTMENT MANAGEMENT ROUTES
    // ->middleware(['permission:appointments:manage'])
    // ADMIN/RECEPTIONIST APPOINTMENT ROUTES - Updated permissions
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);                    // GET /api/appointments
        Route::post('/', [AppointmentController::class, 'store']);                   // POST /api/appointments
        Route::get('/{id}', [AppointmentController::class, 'show']);                 // GET /api/appointments/{id}
        Route::put('/{id}', [AppointmentController::class, 'update']);               // PUT /api/appointments/{id}
        Route::delete('/{id}', [AppointmentController::class, 'destroy']);           // DELETE /api/appointments/{id}
        
        // Appointment actions
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);       // POST /api/appointments/{id}/cancel
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm']);     // POST /api/appointments/{id}/confirm
        Route::post('/{id}/complete', [AppointmentController::class, 'complete']);   // POST /api/appointments/{id}/complete
        Route::post('/{id}/reschedule', [AppointmentController::class, 'reschedule']); // ✅ ADD THIS
    
        // Available slots
        Route::get('/slots/available', [AppointmentController::class, 'availableSlots']); // GET /api/appointments/slots/available
    });

    // PATIENT APPOINTMENT ROUTES - No middleware (service handles validation)
    Route::prefix('patient/appointments')->group(function () {
        Route::get('/', [PatientAppointmentController::class, 'index']);             // GET /api/patient/appointments
        Route::post('/', [PatientAppointmentController::class, 'store']);            // POST /api/patient/appointments
        
        // Patient-specific endpoints
        Route::get('/upcoming', [PatientAppointmentController::class, 'upcoming']); // GET /api/patient/appointments/upcoming
        Route::get('/today', [PatientAppointmentController::class, 'today']);       // GET /api/patient/appointments/today
        Route::get('/next', [PatientAppointmentController::class, 'next']);         // GET /api/patient/appointments/next
        Route::get('/history', [PatientAppointmentController::class, 'history']);   // GET /api/patient/appointments/history
        Route::get('/stats', [PatientAppointmentController::class, 'stats']);       // GET /api/patient/appointments/stats
        
        // Patient actions
        Route::post('/{id}/cancel', [PatientAppointmentController::class, 'cancel']);       // POST /api/patient/appointments/{id}/cancel
        Route::post('/{id}/reschedule', [PatientAppointmentController::class, 'reschedule']); // POST /api/patient/appointments/{id}/reschedule
        
        // Available resources for booking
        Route::get('/doctors/available', [PatientAppointmentController::class, 'availableDoctors']); // GET /api/patient/appointments/doctors/available
        Route::get('/slots/available', [PatientAppointmentController::class, 'availableSlots']);     // GET /api/patient/appointments/slots/available
    });

    // DOCTOR APPOINTMENT ROUTES - No middleware (service handles validation)
    Route::prefix('doctor/appointments')->group(function () {
        Route::get('/', [DoctorAppointmentController::class, 'index']);             // GET /api/doctor/appointments
        
        // Create appointments
        Route::post('/', [DoctorAppointmentController::class, 'store']);
        Route::post('/recurring', [DoctorAppointmentController::class, 'createRecurring']);

        // Helper endpoints
        Route::get('/patients/search', [DoctorAppointmentController::class, 'getAvailablePatients']);
        Route::post('/check-conflicts', [DoctorAppointmentController::class, 'checkConflicts']);

        // Schedule management
        Route::get('/schedule/today', [DoctorAppointmentController::class, 'todaysSchedule']); // GET /api/doctor/appointments/schedule/today
        Route::get('/upcoming', [DoctorAppointmentController::class, 'upcoming']);             // GET /api/doctor/appointments/upcoming
        Route::get('/schedule/date', [DoctorAppointmentController::class, 'scheduleForDate']); // GET /api/doctor/appointments/schedule/date
        Route::get('/availability', [DoctorAppointmentController::class, 'availability']);     // GET /api/doctor/appointments/availability
        Route::get('/stats', [DoctorAppointmentController::class, 'stats']);                   // GET /api/doctor/appointments/stats
        
        // Appointment actions
        Route::post('/{id}/confirm', [DoctorAppointmentController::class, 'confirm']);     // POST /api/doctor/appointments/{id}/confirm
        Route::post('/{id}/complete', [DoctorAppointmentController::class, 'complete']);   // POST /api/doctor/appointments/{id}/complete
        Route::post('/{id}/cancel', [DoctorAppointmentController::class, 'cancel']);       // POST /api/doctor/appointments/{id}/cancel
        Route::post('/{id}/no-show', [DoctorAppointmentController::class, 'markNoShow']); // POST /api/doctor/appointments/{id}/no-show
        Route::post('/{id}/reschedule', [DoctorAppointmentController::class, 'reschedule']); // ✅ ADD THIS
    
        // Time slot management      // POST /api/doctor/appointments/time-slots
        Route::post('/time-slots/block', [DoctorAppointmentController::class, 'blockTimeSlots']); // POST /api/doctor/appointments/time-slots/block
        Route::get('/time-slots/blocked', [DoctorAppointmentController::class, 'blockedSlots']);  // GET /api/doctor/appointments/time-slots/blocked
        Route::delete('/time-slots/blocked/{id}', [DoctorAppointmentController::class, 'unblockTimeSlot']); // DELETE /api/doctor/appointments/time-slots/blocked/{id}

        //  Doctor settings endpoints
        Route::get('/settings', [DoctorAppointmentController::class, 'getSettings']);     // GET /api/doctor/appointments/settings
        Route::put('/settings', [DoctorAppointmentController::class, 'updateSettings']);  // PUT /api/doctor/appointments/settings
    });

    // Personal Info routes (Patient access)
    Route::middleware(['auth:api'])->group(function () {
        // Patient personal info routes
        Route::prefix('patient/profile')->group(function () {
            Route::get('/', [PersonalInfoController::class, 'getProfile']);
            Route::put('/', [PersonalInfoController::class, 'updateProfile']);
            Route::post('/image', [PersonalInfoController::class, 'updateProfileImage']);
        });
        
        // Admin/Staff access to patient personal info
        Route::middleware(['permission:patients:view'])->group(function () {
            Route::get('/patients/{patient}/personal-info', [PersonalInfoController::class, 'getPatientPersonalInfo']);
            Route::put('/patients/{patient}/personal-info', [PersonalInfoController::class, 'updatePatientPersonalInfo'])
                ->middleware(['permission:patients:edit']);
        });
    });
});

// AI Diagnostic routes 
Route::group(['middleware' => ['jwt.auth', 'permission:ai:use'], 'prefix' => 'ai'], function () {
    Route::get('/models', [AiDiagnosticController::class, 'getAvailableModels']);
    Route::post('/analyze', [AiDiagnosticController::class, 'analyzeImage']);
});

// Patient AI Analyses routes
Route::group(['middleware' => ['jwt.auth', 'permission:patients:view-medical']], function () {
    Route::get('/patients/{patient}/ai-analyses', [AiDiagnosticController::class, 'getPatientAnalyses']);
    Route::get('/ai-analyses/{analysis}', [AiDiagnosticController::class, 'getAnalysis']);
});

// Test route
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