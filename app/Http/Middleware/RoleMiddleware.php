<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string $roles Daftar peran yang diizinkan, dipisahkan oleh pipa (|), cth: 'admin|hrd'
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // 1. Cek Autentikasi (Walaupun 'auth:sanctum' sudah ada, ini penting untuk otorisasi)
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated: Token missing or invalid.'], 401);
        }

        // 2. Ambil peran user dari Accessor 'role' (diasumsikan mengembalikan string seperti "admin,hrd")
        // Aksesor ini penting untuk menyelesaikan masalah 'role:null' sebelumnya.
        $userRoleString = $request->user()->role;

        if (empty($userRoleString)) {
            // User tidak memiliki peran apa pun yang terlampir
            return response()->json(['message' => 'Forbidden: User has no assigned roles.'], 403);
        }

        // Pisahkan peran yang diminta dari route (cth: "admin|hrd")
        $requiredRoles = explode('|', $roles);

        // Pisahkan peran user (cth: "admin,hrd")
        $userRoles = array_map('trim', explode(',', $userRoleString));

        // 3. Cek Otorisasi
        // Cek apakah ada irisan (kesamaan) antara peran user dan peran yang diperlukan
        if (empty(array_intersect($userRoles, $requiredRoles))) {
            return response()->json(['message' => 'Forbidden: Insufficient role permissions.'], 403);
        }

        return $next($request);
    }
}
