<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GuardAssignmentController;
use App\Http\Controllers\Api\GuardController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\VisitorController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);

        Route::middleware('role:admin')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);

            Route::apiResource('sites', SiteController::class);
            Route::apiResource('guards', GuardController::class);
            Route::apiResource('shifts', ShiftController::class);
            Route::apiResource('assignments', GuardAssignmentController::class);

            Route::get('/attendance', [AttendanceController::class, 'index']);
            Route::get('/attendance/export', [AttendanceController::class, 'export']);
            Route::get('/attendance/{attendance}', [AttendanceController::class, 'show']);
            Route::put('/attendance/{attendance}/correct', [AttendanceController::class, 'correct']);

            Route::apiResource('visitors', VisitorController::class);
            Route::apiResource('incidents', IncidentController::class);

            Route::get('/leave-requests', [LeaveController::class, 'index']);
            Route::get('/leave-requests/{leave}', [LeaveController::class, 'show']);
            Route::put('/leave-requests/{leave}/status', [LeaveController::class, 'updateStatus']);

            Route::get('/payroll', [PayrollController::class, 'index']);
            Route::post('/payroll/generate', [PayrollController::class, 'generate']);
            Route::get('/payroll/{payroll}', [PayrollController::class, 'show']);
            Route::get('/payroll/{payroll}/payslip', [PayrollController::class, 'payslip']);

            Route::get('/reports/{type}', [ReportController::class, 'show']);
            Route::get('/reports/{type}/export/{format}', [ReportController::class, 'export']);

            Route::get('/settings', [SettingController::class, 'index']);
            Route::put('/settings', [SettingController::class, 'update']);
            Route::get('/holidays', [SettingController::class, 'holidays']);
            Route::post('/holidays', [SettingController::class, 'storeHoliday']);
            Route::delete('/holidays/{holiday}', [SettingController::class, 'destroyHoliday']);

            Route::get('/notifications', [NotificationController::class, 'index']);
        });

        Route::middleware('role:guard')->group(function () {
            Route::post('/check-in', [AttendanceController::class, 'checkIn']);
            Route::post('/check-out', [AttendanceController::class, 'checkOut']);
            Route::get('/my-attendance', [AttendanceController::class, 'myAttendance']);

            Route::post('/leave-requests', [LeaveController::class, 'apply']);
            Route::get('/my-leave-requests', [LeaveController::class, 'myLeaves']);

            Route::get('/my-payroll', [PayrollController::class, 'myPayroll']);
            Route::get('/my-payroll/{payroll}', [PayrollController::class, 'show']);
            Route::get('/my-payroll/{payroll}/payslip', [PayrollController::class, 'myPayslip']);

            Route::get('/my-notifications', [NotificationController::class, 'myNotifications']);
            Route::put('/my-notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('/my-notifications/{notification}', [NotificationController::class, 'destroy']);

            Route::get('/my-visitors', [VisitorController::class, 'myVisitors']);
            Route::post('/visitor-entries', [VisitorController::class, 'storeForGuard']);
            Route::put('/visitor-entries/{visitor}/exit', [VisitorController::class, 'recordExit']);

            Route::get('/my-incidents', [IncidentController::class, 'myIncidents']);
            Route::post('/report-incident', [IncidentController::class, 'storeForGuard']);
        });
    });
});
