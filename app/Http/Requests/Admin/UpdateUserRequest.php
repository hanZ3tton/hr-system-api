<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Tentukan apakah user diizinkan untuk membuat request ini.
     */
    public function authorize(): bool
    {
        // Seperti StoreUserRequest, kita biarkan true karena otorisasi role sudah di controller.
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Mendapatkan ID user dari parameter route (misalnya: users/{user})
        // Jika route Anda menggunakan parameter {id} (users/{id}), gunakan 'id'
        $userId = $this->route('user') ?? $this->route('id');

        return [
            'name' => 'sometimes|string|max:100',
            // Gunakan Rule::unique() dan abaikan ID user saat ini
            'employee_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('users')->ignore($userId)
            ],
            'role_id' => 'sometimes|exists:roles,id',
            'password' => 'nullable|min:6',
            'active' => 'sometimes|boolean', // Diubah menjadi 'sometimes' agar tidak wajib dikirim
        ];
    }
}
