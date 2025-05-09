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
Route::get('/get-available-staff-from-scheduletime', [StaffController::class, 'getAvailableStaffFromScheduledate']);
Route::get('/get-staff-schedule-from-date', [StaffController::class, 'getStaffScheduleFromDate']);

// Schedule routes
Route::get('/schedules', [ScheduleController::class, 'index']);
Route::get('/schedules/{id}', [ScheduleController::class, 'show']);
Route::get('/get-available-shedules', [ScheduleController::class, 'getAvailableShedules']);
Route::get('/get-unavailable-time-from-date', [ScheduleController::class, 'getUnavailableTimeFromDate']);
Route::get('/get-unavailable-time-from-staff', [ScheduleController::class, 'getUnavailableTimeFromStaff']);

// Appointment routes
Route::post('/make-appointment', [AppointmentController::class, 'makeAppointment']);
Route::get('/appointments', [AppointmentController::class, 'index']);
Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
Route::get('/getServiceAppointments/{id}', [AppointmentController::class, 'getServiceAppointments']);
Route::get('/sms', [AppointmentController::class, 'sendSms']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Package management
    Route::apiResource('packages', PackageController::class)->except(['index', 'show']);

    // Service management
    Route::apiResource('services', ServiceController::class)->except(['index', 'show']);

    // Schedule management
    Route::apiResource('schedules', ScheduleController::class)->except(['index', 'show']);
    Route::post('/insertSchedule', [ScheduleController::class, 'insert']);

    // Appointment management
    Route::apiResource('appointments', AppointmentController::class)->except(['index', 'show']);
    Route::get('/getBookedServiceByDate', [AppointmentController::class, 'getBookedServiceByDate']);
    Route::post('/takeBreakAppointment', [AppointmentController::class, 'takeBreakAppointment']);
    Route::get('/getUserBookingHistory', [AppointmentController::class, 'getUserBookingHistory']);
    Route::post('/sendSms', [AppointmentController::class, 'sendSms']);

    // Order management
    Route::apiResource('orders', OrderController::class);
    Route::get('/getOrderByAppointment/{id}', [OrderController::class, 'getOrderByAppointment']);

    // Schedule history management
    Route::apiResource('schedule-histories', ScheduleHistoryController::class);

    // Service appointment management
    Route::apiResource('service-appointments', ServiceAppointmentController::class);

    // Staff management
    Route::apiResource('staff', StaffController::class)->except(['index', 'show']);

    // User management
    Route::apiResource('user', UserController::class);
    Route::post('/import-user', [UserController::class, 'import']);
    Route::get('/search-user-by-field', [UserController::class, 'getByField']);

    // System settings
    Route::apiResource('system-setting', SystemSettingController::class);
    Route::get('/getSystemSettingByKey', [SystemSettingController::class, 'getSystemSettingByKey']);
});

