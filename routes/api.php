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
use App\Http\Controllers\Api\DoctorPatientController;
use App\Http\Controllers\Api\PersonalInfoController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\AppointmentReminderController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\MedicalHistoryController;

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
    // Applying explicit permission middleware for each action as per seeded permissions.
    Route::get('users/counts-by-status', [UserController::class, 'countsByStatus'])
        ->middleware('permission:users:view');
    Route::apiResource('users', UserController::class)->middleware([
        'index'   => 'permission:users:view',
        'show'    => 'permission:users:view',
        'store'   => 'permission:users:create',
        'update'  => 'permission:users:edit',
        'destroy' => 'permission:users:delete',
    ]);
    Route::post('users/{user}/roles', [UserController::class, 'assignRoles']);
    Route::get('users/{user}/roles', [UserController::class, 'getUserRoles']);
    Route::get('users/{user}/permissions', [UserController::class, 'getUserPermissions']);
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('permission:users:reset-password');

    // Role routes
    Route::get('roles/check-name', [RoleController::class, 'checkNameExists']);
    Route::apiResource('roles', RoleController::class)->middleware([
        'index'   => 'permission:roles:view',
        'show'    => 'permission:roles:view',
        'store'   => 'permission:roles:create',
        'update'  => 'permission:roles:edit',
        'destroy' => 'permission:roles:delete',
    ]);
    Route::post('roles/{role}/permissions', [RoleController::class, 'assignPermissions'])
        ->middleware('permission:roles:assign-permissions');

    // Permission routes
    // Adding permission middleware to view permissions and groups.
    Route::get('permissions', [PermissionController::class, 'index'])
        ->middleware('permission:permissions:view');
    Route::get('permissions/groups', [PermissionController::class, 'getGroups'])
        ->middleware('permission:permissions:view-groups');
    Route::get('permissions/groups/list', [PermissionController::class, 'listGroups'])
        ->middleware('permission:permissions:view-groups');

    // Patient routes
    // Applying explicit permission middleware for patient resource.
    Route::apiResource('patients', PatientController::class)->middleware([
        'index'   => 'permission:patients:view',
        'show'    => 'permission:patients:view',
        'store'   => 'permission:patients:create',
        'update'  => 'permission:patients:edit',
        'destroy' => 'permission:patients:delete',
    ]);

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
    Route::get('patient/bills/{bill}/receipt', [BillController::class, 'downloadBillReceiptForPatient'])->name('patient.bills.receipt.download');

    // APPOINTMENT MANAGEMENT ROUTES
    // ADMIN/RECEPTIONIST APPOINTMENT ROUTES - Updated permissions
    Route::prefix('appointments')->middleware(['permission:appointments:manage'])->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);                    // GET /api/appointments
        Route::post('/', [AppointmentController::class, 'store']);                   // POST /api/appointments
        Route::get('/{id}', [AppointmentController::class, 'show']);                 // GET /api/appointments/{id}
        Route::put('/{id}', [AppointmentController::class, 'update']);               // PUT /api/appointments/{id}
        Route::delete('/{id}', [AppointmentController::class, 'destroy']);           // DELETE /api/appointments/{id}
        
        // Appointment actions
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);       // POST /api/appointments/{id}/cancel
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm']);     // POST /api/appointments/{id}/confirm
        Route::post('/{id}/complete', [AppointmentController::class, 'complete']);   // POST /api/appointments/{id}/complete
        Route::post('/{id}/reschedule', [AppointmentController::class, 'reschedule']); // POST /api/appointments/{id}/reschedule
    
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

    // MEDICAL RECORDS API ROUTES
    Route::group(['middleware' => ['jwt.auth']], function () {
        
        // Patient Medical Data - Comprehensive endpoints
        Route::prefix('patients/{patient}/medical')->group(function () {
            Route::get('/summary', [App\Http\Controllers\Api\PatientMedicalController::class, 'summary']);
            Route::get('/vitals', [App\Http\Controllers\Api\PatientMedicalController::class, 'vitals']);
            Route::get('/medications', [App\Http\Controllers\Api\PatientMedicalController::class, 'medications']);
            Route::get('/lab-results', [App\Http\Controllers\Api\PatientMedicalController::class, 'labResults']);
            Route::get('/medical-history', [App\Http\Controllers\Api\PatientMedicalController::class, 'medicalHistory']);
            Route::get('/timeline', [App\Http\Controllers\Api\PatientMedicalController::class, 'timeline']);
            Route::get('/files', [App\Http\Controllers\Api\PatientMedicalController::class, 'files']);
            Route::get('/notes', [App\Http\Controllers\Api\PatientMedicalController::class, 'notes']);
            Route::get('/alerts', [App\Http\Controllers\Api\PatientMedicalController::class, 'alerts']);
            Route::get('/statistics', [App\Http\Controllers\Api\PatientMedicalController::class, 'statistics']);
        });

        // Medical History Management
        Route::prefix('patients/{patient}/medical-histories')->group(function () {
            Route::get('/', [MedicalHistoryController::class, 'index']);
            Route::post('/', [MedicalHistoryController::class, 'store']);
            Route::get('/{id}', [MedicalHistoryController::class, 'show']);
            Route::put('/{id}', [MedicalHistoryController::class, 'update']);
            Route::delete('/{id}', [MedicalHistoryController::class, 'destroy']);
        });
        
        // Patient Medical Data - Legacy endpoint for backwards compatibility
        Route::get('patients/{patient}/medical-data', [PatientController::class, 'getMedicalData']);

        // Vital Signs Management
        Route::apiResource('vital-signs', App\Http\Controllers\Api\VitalSignController::class);

        // Medications Management  
        Route::apiResource('medications', App\Http\Controllers\Api\MedicationController::class);
        Route::post('medications/{medication}/discontinue', [App\Http\Controllers\Api\MedicationController::class, 'discontinue']);

        // Lab Results Management
        Route::apiResource('lab-results', App\Http\Controllers\Api\LabResultController::class);
        Route::get('patients/{patient}/lab-results/{testName}/history', [App\Http\Controllers\Api\LabResultController::class, 'history']);

        // Patient Files Management
        Route::apiResource('patient-files', App\Http\Controllers\Api\PatientFileController::class);
        Route::get('patient-files/{file}/download', [App\Http\Controllers\Api\PatientFileController::class, 'download'])->name('patient-files.download');
        Route::get('patient-files-categories', [App\Http\Controllers\Api\PatientFileController::class, 'categories']);

        // Patient Notes Management
        Route::apiResource('patient-notes', App\Http\Controllers\Api\PatientNoteController::class);
        Route::get('patient-note-types', [App\Http\Controllers\Api\PatientNoteController::class, 'types']);

        // Timeline Events (Read-only)
        Route::get('timeline-events/summary', [App\Http\Controllers\Api\TimelineEventController::class, 'summary']); // Must be BEFORE apiResource
        Route::apiResource('timeline-events', App\Http\Controllers\Api\TimelineEventController::class, [
            'only' => ['index', 'show']
        ]);

        // Patient Alerts Management
        Route::apiResource('patient-alerts', App\Http\Controllers\Api\PatientAlertController::class);
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

// DOCTOR PATIENT MANAGEMENT ROUTES - Phase 1
Route::group(['middleware' => ['jwt.auth']], function () {
    
    // Doctor Patient Management
    Route::prefix('doctor/patients')->group(function () {
        Route::get('/my-patients', [App\Http\Controllers\Api\DoctorPatientController::class, 'getMyPatients']);
        Route::get('/{patient}/summary', [App\Http\Controllers\Api\DoctorPatientController::class, 'getPatientSummary']);
        Route::get('/search', [App\Http\Controllers\Api\DoctorPatientController::class, 'searchPatients']);
        Route::get('/alerts/critical', [App\Http\Controllers\Api\DoctorPatientController::class, 'getCriticalAlerts']);
        Route::get('/dashboard/stats', [App\Http\Controllers\Api\DoctorPatientController::class, 'getDashboardStats']);
        Route::get('/activity/recent', [App\Http\Controllers\Api\DoctorPatientController::class, 'getRecentActivity']);
        
        // ✅ NEW: Phase 2 - Patient Demographics Endpoints
        Route::get('/demographics/gender', [App\Http\Controllers\Api\DoctorPatientController::class, 'getGenderDemographics']);     // GET /api/doctor/patients/demographics/gender
        Route::get('/demographics/age', [App\Http\Controllers\Api\DoctorPatientController::class, 'getAgeDemographics']);           // GET /api/doctor/patients/demographics/age
        Route::get('/demographics/overview', [App\Http\Controllers\Api\DoctorPatientController::class, 'getDemographicsOverview']); // GET /api/doctor/patients/demographics/overview
    });
});

// REMINDER MANAGEMENT ROUTES
Route::group(['middleware' => ['jwt.auth']], function () {
    
    // Main Reminder Routes
    Route::prefix('reminders')->group(function () {
        
        // User reminder settings (All authenticated users)
        Route::get('/settings', [ReminderController::class, 'getReminderSettings']);
        Route::put('/settings', [ReminderController::class, 'updateReminderSettings']);
        
        // Patient-specific routes
        Route::get('/upcoming', [ReminderController::class, 'getUpcomingReminders']);
        Route::post('/opt-out', [ReminderController::class, 'optOutReminders']);
        
        // Admin/Staff routes for reminder management
        Route::group(['middleware' => ['permission:appointments:manage']], function () {
            Route::post('/schedule', [ReminderController::class, 'scheduleReminders']);
            Route::post('/cancel', [ReminderController::class, 'cancelReminders']);
            Route::post('/test', [ReminderController::class, 'sendTestReminder']);
            Route::get('/logs', [ReminderController::class, 'getReminderLogs']);
            Route::get('/analytics', [ReminderController::class, 'getReminderAnalytics']);
            Route::post('/bulk', [ReminderController::class, 'bulkReminderOperation']);
        });
    });

    // Appointment-specific Reminder Routes
    Route::prefix('appointments/{appointment}/reminders')->group(function () {
        
        // View reminders for appointment (appointment owner or staff)
        Route::get('/', [AppointmentReminderController::class, 'getAppointmentReminders']);
        Route::get('/status', [AppointmentReminderController::class, 'getReminderDeliveryStatus']);
        
        // Patient routes (appointment owner only)
        Route::put('/preferences', [AppointmentReminderController::class, 'updateReminderPreferences']);
        Route::post('/{reminderLog}/acknowledge', [AppointmentReminderController::class, 'acknowledgeReminder']);
        
        // Admin/Staff routes for managing appointment reminders
        Route::group(['middleware' => ['permission:appointments:manage']], function () {
            Route::post('/custom', [AppointmentReminderController::class, 'scheduleCustomReminder']);
            Route::delete('/{reminder}', [AppointmentReminderController::class, 'cancelReminder']);
            Route::put('/{reminder}/reschedule', [AppointmentReminderController::class, 'rescheduleReminder']);
            Route::post('/test', [AppointmentReminderController::class, 'testReminderDelivery']);
        });
    });
});

// In-App Notifications Routes (Authenticated Users)
Route::middleware(['auth:api'])->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);                    // GET /api/notifications
    Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']); // GET /api/notifications/unread-count
    Route::get('/upcoming-reminders', [NotificationController::class, 'getUpcomingReminders']); // GET /api/notifications/upcoming-reminders
    Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']); // PUT /api/notifications/mark-all-read
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);      // PUT /api/notifications/{id}/read
    Route::delete('/clear-read', [NotificationController::class, 'clearRead']);   // DELETE /api/notifications/clear-read (BEFORE {id})
    Route::delete('/{id}', [NotificationController::class, 'destroy']);          // DELETE /api/notifications/{id}
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