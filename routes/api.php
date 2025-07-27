<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleHistoryController;
use App\Http\Controllers\ServiceAppointmentController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AppointmentLogController;


// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Package routes
Route::get('/packages', [PackageController::class, 'index']);
Route::get('/packages/{id}', [PackageController::class, 'show']);
Route::get('/packages-with-service', [PackageController::class, 'getPackageWithService']);

// Service routes
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('get-service-by-package/{id}', [ServiceController::class, 'getServiceByPackage']);

// Staff routes
Route::get('/staff', [StaffController::class, 'index']);
Route::get('/staff/{id}', [StaffController::class, 'show']);
Route::get('/get-available-staff-from-schedule-date', [StaffController::class, 'getAvailableStaffFromScheduleDate']);

// Schedule routes
Route::get('/schedules', [ScheduleController::class, 'index']);
Route::get('/schedules/{id}', [ScheduleController::class, 'show']);
Route::get('/get-available-schedules', [ScheduleController::class, 'getAvailableSchedules']);
Route::get('/get-unavailable-time-from-date', [ScheduleController::class, 'getUnavailableTimeFromDate']);
Route::get('/get-unavailable-time-from-staff', [ScheduleController::class, 'getUnavailableTimeFromStaff']);

// Appointment routes
Route::post('/make-appointment', [AppointmentController::class, 'makeAppointment']);

Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
Route::get('/getServiceAppointments/{id}', [AppointmentController::class, 'getServiceAppointments']);

// User profile routes
Route::apiResource('user-profile', UserProfileController::class);
Route::get('get-profile-by-userId', [UserProfileController::class, 'getProfileByUser']);
Route::post('/user-profile/{id}', [UserProfileController::class, 'update']);

// User routes
Route::get('/find-user-by-field', [UserController::class, 'findByField']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Package management
    Route::apiResource('packages', PackageController::class)->except(['index', 'show']);

    // Service management
    Route::apiResource('services', ServiceController::class)->except(['index', 'show']);

    // Schedule management
    Route::apiResource('schedules', ScheduleController::class)->except(['index', 'show']);
    Route::post('/insert-schedule', [ScheduleController::class, 'insert']);
    Route::get('/getStaffSchedule', [ScheduleController::class, 'getStaffSchedule']);

    // Appointment management
    Route::apiResource('appointments', AppointmentController::class)->except(['show']);
    Route::post('/takeBreakAppointment', [AppointmentController::class, 'takeBreakAppointment']);
    Route::get('/getUserBookingHistory', [AppointmentController::class, 'getUserBookingHistory']);
    Route::post('/sendSms', [AppointmentController::class, 'sendSms']);
    Route::post('/appointments/mark-no-show', [AppointmentController::class, 'makeNoShow']);
    Route::get('/getBookedServiceByDate', [AppointmentController::class, 'getBookedServiceByDate']);

    // Order management
    Route::apiResource('orders', OrderController::class);
    Route::get('/getOrderByAppointment/{id}', [OrderController::class, 'getOrderByAppointment']);
    Route::post('/orders/finishOrder', [OrderController::class, 'finishOrder']);

    // Schedule history management
    Route::apiResource('/schedule-histories', ScheduleHistoryController::class);

    // Service appointment management
    Route::apiResource('/service-appointments', ServiceAppointmentController::class);

    // Appointment log management
    Route::get('/appointment-logs', [AppointmentLogController::class, 'getLogsByDateRange']);

    // Staff management
    Route::apiResource('staff', StaffController::class)->except(['index', 'show']);
    Route::get('/get-staff-schedule-from-date', [StaffController::class, 'getStaffScheduleFromDate']);

    // User management
    Route::apiResource('user', UserController::class);
    Route::post('/import-user', [UserController::class, 'import']);
    Route::get('/search-user-by-field', [UserController::class, 'getByKeyword']);

    Route::post('/upload-attachment/{id}', [UserProfileController::class, 'uploadAttachment']);
    Route::delete('/delete-attachment/{id}', [UserProfileController::class, 'deleteAttachment']);

    // Voucher management
    Route::apiResource('vouchers', VoucherController::class);
    Route::post('/vouchers/bulk', [VoucherController::class, 'bulkStore']);
    Route::post('/vouchers/verify', [VoucherController::class, 'verify']);
    Route::post('/vouchers/verifyValidCode', [VoucherController::class, 'verifyValidCode']);

    // System settings
    Route::apiResource('system-setting', SystemSettingController::class);
    Route::get('/getSystemSettingByKey', [SystemSettingController::class, 'getSystemSettingByKey']);

    // Statistics routes
    Route::get('/getTodayStatistics', [AppointmentController::class, 'getTodayStatistics']);
    Route::get('/getStaffIncomeStatistics', [StaffController::class, 'getStaffIncomeStatistics']);
    Route::get('/getStaffScheduleStatistics', [ScheduleController::class, 'getStaffScheduleStatistics']);
    Route::get('/getTotalStatistics', [AppointmentController::class, 'getTotalStatistics']);

    //notification
    Route::get('/getNotification', [NotificationController::class, 'getNotification']);
    Route::get('/getSmsBalance', [NotificationController::class, 'getSmsBalance']);
});

Route::get('/phpinfo', function () {
    phpinfo();
});

