<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Class AuthController
 * @package App\Http\Controllers\API
 * Mengelola semua operasi otentikasi (login, logout, profil) untuk API.
 */
class AuthController extends Controller
{
    // =========================================================================
    // LOGIN
    // =========================================================================
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * Menangani proses login pengguna.
     */
    public function login(Request $request)
    {
        // 1. Validasi input: Pastikan employee_number dan password tersedia
        $request->validate([
            'employee_number' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Cari pengguna berdasarkan employee_number dan EAGER LOAD relasi 'roles'
        // Eager loading diperlukan agar accessor 'role' di model User dapat bekerja.
        $user = User::where('employee_number', $request->employee_number)
            ->with('roles')
            ->first();

        // 3. Verifikasi pengguna dan password
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Respons 401 Unauthorized jika kredensial tidak valid
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // 4. Buat token autentikasi Sanctum
        $token = $user->createToken('api-token')->plainTextToken;

        // 5. Berikan respons sukses
        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'employee' => $user->employee_number,
                // MENGGUNAKAN ACCESSOR: Cukup akses $user->role.
                // Accessor getRoleAttribute() di Model User akan mengembalikan string role.
                'role' => $user->role,
            ],
            'token' => $token, // Token yang akan digunakan di header Authorization
        ]);
    }

    // =========================================================================
    // LOGOUT
    // =========================================================================
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * Menghapus token Sanctum saat ini, yang secara efektif me-logout pengguna.
     * Membutuhkan otentikasi.
     */
    public function logout(Request $request)
    {
        // Hapus token yang digunakan untuk request saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    // =========================================================================
    // PROFILE
    // =========================================================================
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * Mengembalikan detail profil pengguna yang sedang login.
     * Membutuhkan otentikasi.
     */
    public function profile(Request $request)
    {
        // EAGER LOAD relasi 'roles' pada objek user yang sudah terotentikasi.
        // Ini memastikan accessor 'role' mendapatkan data relasi yang dibutuhkan.
        $user = $request->user()->load('roles');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'employee' => $user->employee_number,
                // MENGGUNAKAN ACCESSOR: Cukup akses $user->role
                'role' => $user->role,
            ]
        ]);
    }
}
