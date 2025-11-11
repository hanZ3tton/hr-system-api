<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Tentukan apakah user diizinkan untuk membuat request ini.
     * Karena otorisasi role 'admin' sudah ada di controller, kita bisa set true.
     * Atau, jika ingin memindahkan otorisasi 'admin' ke sini:
     */
    public function authorize(): bool
    {
        // Mendapatkan user yang sedang login
        $user = $this->user();

        // Asumsikan controller telah mengimplementasikan authorizeRole atau menggunakan middleware
        // Jika Anda ingin memindahkan logika otorisasi ke sini (lebih disarankan):
        // if (!$user || !str_contains($user->role, 'admin')) {
        //     return false;
        // }
        // return true;

        // Untuk saat ini, kita biarkan true karena otorisasi role sudah di controller.
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'employee_number' => 'required|string|max:50|unique:users,employee_number',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
            'active' => 'boolean',
        ];
    }
}
