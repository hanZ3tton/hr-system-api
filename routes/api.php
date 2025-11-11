<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\LeaveController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// PUBLIC ROUTES
// =========================================================================
// PERBAIKAN KRITIS: Tambahkan ->name('login')
Route::post('login', [AuthController::class, 'login'])->name('login');

// =========================================================================
// PROTECTED ROUTES (Memerlukan Sanctum Authentication)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {
    // --- Authentication & Profile ---
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [AuthController::class, 'profile']);

    // --- Attendance Management (Absensi) ---
    Route::prefix('attendance')->group(function () {
        Route::post('check-in', [AttendanceController::class, 'checkIn']);
        Route::post('check-out', [AttendanceController::class, 'checkOut']);
        Route::get('history', [AttendanceController::class, 'history']);
    });

    // --- Leave Application (Cuti/Izin) ---
    // Employee: create and list own leaves
    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);

    // =========================================================================
    // ADMIN/HRD ONLY ROUTES (Memerlukan Role 'admin' atau 'hrd')
    // =========================================================================
    Route::middleware('role:admin|hrd')->prefix('admin')->group(function () {

        // --- User Management (Rute CRUD manual untuk UserController) ---
        // Ini menggantikan Route::apiResource('users', UserController::class);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::patch('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // --- HR/Admin: approve/reject ---
        // Catatan: Pastikan di LeaveController, parameter yang diterima adalah {leave} atau {leaveRequest} 
        // agar sesuai dengan model binding. Saya gunakan {leave} untuk kesederhanaan.
        Route::patch('/leaves/{leave}/status', [LeaveController::class, 'updateStatus']);
    });
});
