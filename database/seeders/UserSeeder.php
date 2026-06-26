<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departmentId = Department::query()->where('code', 'CCS')->value('id');

        $users = [
            [
                'name' => 'Demo Student',
                'email' => 'student@example.com',
                'role' => User::ROLE_STUDENT,
                'student_number' => '20260001',
                'department_id' => $departmentId,
            ],
            [
                'name' => 'Demo Admin',
                'email' => 'admin@example.com',
                'role' => User::ROLE_ADMIN,
                'student_number' => null,
                'department_id' => $departmentId,
            ],
            [
                'name' => 'Demo Superadmin',
                'email' => 'superadmin@example.com',
                'role' => User::ROLE_SUPERADMIN,
                'student_number' => null,
                'department_id' => null,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                $user + [
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
