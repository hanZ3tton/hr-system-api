<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $departments = [
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance and Accounting',
                'code' => 'FA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('departments')->truncate();
        DB::table('departments')->insert($departments);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Departments Seeding Completed!');
    }
}
