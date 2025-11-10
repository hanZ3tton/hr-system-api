<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\LeaveController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// PUBLIC ROUTES
// Rute yang dapat diakses tanpa autentikasi
// =========================================================================
Route::post('login', [AuthController::class, 'login']);

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
        // Rute baru: Untuk melihat riwayat absensi pengguna yang sedang login
        Route::get('history', [AttendanceController::class, 'history']);
    });

    // --- Leave Application (Cuti/Izin) ---
    Route::apiResource('leaves', LeaveController::class);

    // =========================================================================
    // ADMIN/HRD ONLY ROUTES (Memerlukan Role 'admin' atau 'hrd')
    // =========================================================================
    Route::middleware('role:admin|hrd')->prefix('admin')->group(function () {
        // --- User Management ---
        Route::apiResource('users', UserController::class);

        // Tambahkan rute untuk Admin/HRD di sini, misalnya untuk melihat
        // laporan absensi atau persetujuan cuti dari semua pengguna.
    });
});
