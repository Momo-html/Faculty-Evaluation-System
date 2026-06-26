<?php

namespace Tests\Feature;

use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_admin_can_sign_in_to_admin_portal(): void
    {
        $this->seed([DepartmentSeeder::class, UserSeeder::class]);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_demo_student_can_sign_in_to_student_portal(): void
    {
        $this->seed([DepartmentSeeder::class, UserSeeder::class]);

        $this->post('/user/login', [
            'email' => 'student@example.com',
            'password' => 'password',
        ])->assertRedirect('/user/home');

        $this->get('/user/home')->assertOk();
    }

    public function test_demo_superadmin_can_sign_in_to_superadmin_console(): void
    {
        $this->seed([DepartmentSeeder::class, UserSeeder::class]);

        $this->post('/superadmin/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ])->assertRedirect('/superadmin/dashboard');

        $this->get('/superadmin/dashboard')->assertOk();
    }
}
