<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // =========================================================================
    // HELPER: Pengecekan Otorisasi (Menggunakan Accessor $user->role)
    // =========================================================================

    /**
     * Memeriksa apakah user memiliki salah satu role yang diizinkan.
     * $user->role diasumsikan mengembalikan string, contoh: "admin,hrd"
     */
    private function authorizeRole(User $user, array $allowedRoles)
    {
        $roleString = $user->role; // Mengambil string peran dari Accessor

        // Cek apakah string peran mengandung setidaknya satu dari peran yang diizinkan
        foreach ($allowedRoles as $role) {
            // str_contains lebih aman untuk string gabungan daripada '==='
            if (str_contains($roleString, $role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * List all users (Admin/HRD allowed)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $allowedRoles = ['admin', 'hrd'];

        if (!$this->authorizeRole($user, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Gunakan 'roles' (jamak) untuk eager loading relasi BelongsToMany
        $users = User::with('roles:id,name')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Show a specific user (Admin/HRD allowed)
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $allowedRoles = ['admin', 'hrd'];

        if (!$this->authorizeRole($user, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Gunakan 'roles' (jamak) untuk eager loading
        $target = User::with('roles:id,name')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $target
        ]);
    }

    /**
     * Create new user (Admin only)
     */
    public function store(Request $request)
    {
        $authUser = $request->user();

        // Pengecekan hanya untuk 'admin'
        if (!$this->authorizeRole($authUser, ['admin'])) {
            return response()->json(['message' => 'Unauthorized: Only Admin can create users.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'employee_number' => 'required|string|max:50|unique:users,employee_number',
            'password' => 'required|min:6',
            // Pastikan role_id wajib saat membuat user baru
            'role_id' => 'required|exists:roles,id',
            'active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['active'] = $request->input('active', true);

        $user = User::create($validated);

        // PENTING: Attach role ke user baru
        // FIX: Mengirim array kedua untuk data pivot tambahan (assigned_by)
        $user->roles()->attach($validated['role_id'], [
            'assigned_by' => $authUser->id, // Menggunakan ID user yang sedang login
        ]);

        // Gunakan 'roles' (jamak) untuk load data
        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user->load('roles:id,name')
        ], 201);
    }

    /**
     * Update user info (Admin only)
     */
    public function update(Request $request, $id)
    {
        $authUser = $request->user();

        // Pengecekan hanya untuk 'admin'
        if (!$this->authorizeRole($authUser, ['admin'])) {
            return response()->json(['message' => 'Unauthorized: Only Admin can update users.'], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'employee_number' => ['sometimes', 'string', Rule::unique('users')->ignore($user->id)],
            'role_id' => 'sometimes|exists:roles,id', // 'sometimes' agar tidak wajib dikirim
            'password' => 'nullable|min:6',
            'active' => 'boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Update data user di tabel 'users'
        $user->update($validated);

        // =================================================================
        // LOGIKA PIVOT (Efisiensi Tinggi)
        // =================================================================

        // Cek apakah role_id dikirim dalam request (artinya ada niat mengubah peran)
        $isRoleChanged = isset($validated['role_id']);

        if ($isRoleChanged) {
            // 1. Jika role_id dikirim: JALANKAN SYNC
            // FIX KRITIS: Menggunakan sync() untuk memastikan user hanya punya satu peran
            $user->roles()->sync([
                $validated['role_id'] => ['assigned_by' => $authUser->id]
            ]);
        }

        // Memuat ulang relasi roles untuk mendapatkan status pivot terbaru
        $user->load('roles');

        // Ambil data pivot yang pertama (asumsi user hanya punya 1 role)
        // Menggunakan null coalescing operator untuk menghindari error jika user tidak punya role
        $pivot = $user->roles->first()->pivot ?? null;

        // 2. Jika role_id TIDAK dikirim TAPI assigned_by masih NULL, PERBAIKI!
        // Logic ini hanya dijalankan SATU KALI untuk memperbaiki data lama.
        if (!$isRoleChanged && $pivot && is_null($pivot->assigned_by)) {
            // Perbaikan efisien: Hanya update kolom assigned_by di tabel pivot
            $user->roles()->updateExistingPivot($pivot->role_id, [
                'assigned_by' => $authUser->id, // ID Admin yang sedang login
            ]);

            // Memuat ulang lagi untuk respons (karena pivot baru saja diupdate)
            $user->load('roles');
        }

        // Mengembalikan respons dengan data user yang sudah dimuat roles-nya
        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user
        ]);
    }

    /**
     * Soft delete user (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $authUser = $request->user();

        // Pengecekan hanya untuk 'admin'
        if (!$this->authorizeRole($authUser, ['admin'])) {
            return response()->json(['message' => 'Unauthorized: Only Admin can delete users.'], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.'
        ]);
    }
}
