<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ModuleStructureTest extends TestCase
{
    public function test_public_auth_pages_render_successfully(): void
    {
        $paths = [
            '/admin/login',
            '/user/login',
            '/superadmin/login',
            '/password/reset',
            '/password/reset/example-token',
        ];

        foreach ($paths as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_guest_users_are_redirected_to_the_right_login_page(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->get('/superadmin/dashboard')->assertRedirect('/superadmin/login');
        $this->get('/user/home')->assertRedirect('/user/login');
    }

    public function test_role_pages_render_for_their_allowed_role(): void
    {
        $admin = User::factory()->admin()->make();
        $superadmin = User::factory()->superadmin()->make();
        $student = User::factory()->make();

        foreach ($this->adminPaths() as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }

        $this->actingAs($superadmin)->get('/superadmin/dashboard')->assertOk();

        foreach ($this->studentPaths() as $path) {
            $this->actingAs($student)->get($path)->assertOk();
        }
    }

    public function test_role_pages_reject_the_wrong_role(): void
    {
        $admin = User::factory()->admin()->make();
        $student = User::factory()->make();

        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/user/home')->assertForbidden();
        $this->actingAs($admin)->get('/superadmin/dashboard')->assertForbidden();
    }

    public function test_removed_admin_subject_and_section_module_routes_are_not_registered(): void
    {
        $admin = User::factory()->admin()->make();

        $this->actingAs($admin)->get('/admin/sections')->assertNotFound();
        $this->actingAs($admin)->get('/admin/subjects')->assertNotFound();
    }

    /**
     * @return list<string>
     */
    private function adminPaths(): array
    {
        return [
            '/admin/dashboard',
            '/admin/faculty',
            '/admin/forms',
            '/admin/mapping',
            '/admin/reports/faculty-pdf',
            '/admin/security',
            '/admin/sentiment',
            '/admin/settings',
            '/admin/students',
            '/admin/users',
        ];
    }

    /**
     * @return list<string>
     */
    private function studentPaths(): array
    {
        return [
            '/user/home',
            '/user/eval-form',
            '/user/settings',
        ];
    }
}
