<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $hrDepartment = Department::where('code', 'HR')->first();

        // Pengecekan Ketergantungan
        if (!$adminRole || !$hrDepartment) {
            $this->command->error("Seeder gagal: Pastikan RolesSeeder dan DepartmentsSeeder dijalankan terlebih dahulu!");
            return;
        }

        // Membuat atau mendapatkan User Admin
        $admin = User::firstOrCreate(
            ['employee_number' => 'ADM001'],
            [
                'name' => 'AdminBaik',
                'phone' => '081234567890',
                'department_id' => $hrDepartment->id,
                'is_active' => true,
                'timezone' => 'Asia/Jakarta',
                'password' => Hash::make('password'),
                'last_login_at' => now(),
            ]
        );

        // Menghubungkan User Admin dengan Role 'Admin'
        if (!$admin->roles()->where('role_id', $adminRole->id)->exists()) {

            $admin->roles()->syncWithoutDetaching([ // Menggunakan syncWithoutDetaching lebih rapi untuk Many-to-Many
                $adminRole->id => [
                    'assigned_by' => $admin->id,
                    'created_at' => now(),
                ]
            ]);

            $this->command->info("Pengguna Admin (ADM001) berhasil dibuat dan diberi peran 'Admin'.");
        }
    }
}
