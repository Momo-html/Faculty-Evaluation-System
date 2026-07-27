<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DirectoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_archive_faculty_with_a_login(): void
    {
        [$admin, $department] = $this->adminAndDepartment();
        $this->actingAs($admin)->post(route('admin.faculty.store'), [
            'employee_id' => 'FAC-100', 'faculty_name' => 'Ada Faculty', 'email' => 'ada@school.test',
            'department_id' => $department->id, 'status' => 'active', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasNoErrors();

        $faculty = Faculty::query()->where('employee_id', 'FAC-100')->firstOrFail();
        $this->assertDatabaseHas('users', ['id' => $faculty->user_id, 'role' => User::ROLE_FACULTY]);
        $this->actingAs($admin)->delete(route('admin.faculty.destroy', $faculty))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($faculty);
    }

    public function test_student_section_and_subject_assignments_are_consistent(): void
    {
        [$admin, $department] = $this->adminAndDepartment();
        $section = Section::query()->create(['section_name' => 'BSIT 1A', 'department_id' => $department->id, 'year_level' => 1]);
        $subject = Subject::query()->create(['subject_code' => 'IT101', 'subject_name' => 'Intro', 'department_id' => $department->id, 'units' => 3]);
        $faculty = Faculty::query()->create(['employee_id' => 'FAC-1', 'faculty_name' => 'Teacher', 'email' => 'teacher@test.local', 'department_id' => $department->id]);
        $mapping = SubjectMapping::query()->create(['faculty_id' => $faculty->id, 'subject_id' => $subject->id, 'section_id' => $section->id, 'school_year' => '2026-2027', 'semester' => '1st Semester']);

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'student_number' => '2026-001', 'name' => 'Test Student', 'email' => 'student1@test.local',
            'department_id' => $department->id, 'section_id' => $section->id, 'status' => 'active', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasNoErrors();
        $student = User::query()->where('student_number', '2026-001')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.students.assignments.store', $student), ['subject_mapping_id' => $mapping->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_subjects', ['user_id' => $student->id, 'subject_mapping_id' => $mapping->id]);
        $this->assertDatabaseHas('student_section_allocations', ['user_id' => $student->id, 'section_id' => $section->id]);
    }

    public function test_csv_import_rejects_duplicates_and_keeps_valid_rows(): void
    {
        [$admin] = $this->adminAndDepartment();
        $csv = "student_number,name,email,department_code,section_name,password\n2026-010,First Student,first@test.local,CCS,,password123\n2026-010,Duplicate Student,second@test.local,CCS,,password123\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);
        $this->actingAs($admin)->post(route('admin.directory.import'), ['type' => 'student', 'csv_file' => $file])->assertSessionHas('warning');
        $this->assertDatabaseHas('users', ['student_number' => '2026-010']);
        $this->assertDatabaseCount('csv_import_errors', 1);
    }

    public function test_students_and_faculty_pages_reject_student_users(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'status' => 'active']);
        $this->actingAs($student)->get(route('admin.faculty'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.students'))->assertForbidden();
    }

    public function test_directory_edit_controls_render_as_modal_dialogs(): void
    {
        [$admin, $department] = $this->adminAndDepartment();
        Faculty::query()->create(['employee_id' => 'FAC-MODAL', 'faculty_name' => 'Modal Faculty', 'email' => 'modal.faculty@test.local', 'department_id' => $department->id, 'status' => 'active']);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'student_number' => 'MODAL-001', 'department_id' => $department->id, 'status' => 'active']);

        $this->actingAs($admin)->get(route('admin.faculty'))
            ->assertOk()->assertSee('dialog class="directory-modal"', false)->assertDontSee('<details>', false);
        $this->actingAs($admin)->get(route('admin.students'))
            ->assertOk()->assertSee('dialog class="directory-modal"', false)->assertDontSee('<details>', false);
    }

    private function adminAndDepartment(): array
    {
        $department = Department::query()->create(['department_name' => 'Computer Studies', 'code' => 'CCS']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active', 'department_id' => $department->id]);

        return [$admin, $department];
    }
}
