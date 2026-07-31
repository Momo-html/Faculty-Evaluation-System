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

    public function test_account_creation_csv_can_be_imported(): void
    {
        [$admin] = $this->adminAndDepartment();
        $csv = "user_id,login_id,first_name,last_name,full_name,sortable_name,short_name,email,status\n".
            "U-100,ada.login,Ada,Lovelace,Ada Lovelace,\"Lovelace, Ada\",Ada,ada@test.local,active\n";
        $file = UploadedFile::fake()->createWithContent('account_creation.csv', $csv);

        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'account_creation',
            'csv_file' => $file,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('account_creations', [
            'user_id' => 'U-100',
            'login_id' => 'ada.login',
            'full_name' => 'Ada Lovelace',
            'status' => 'active',
        ]);
    }

    public function test_course_creation_csv_can_be_imported(): void
    {
        [$admin] = $this->adminAndDepartment();
        $csv = "course_id,short_name,long_name,status,term_id\n".
            "IT101,IT 101,Introduction to Information Technology,active,2026-1\n";
        $file = UploadedFile::fake()->createWithContent('course_creation.csv', $csv);

        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'course_creation',
            'csv_file' => $file,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('course_creations', [
            'course_id' => 'IT101',
            'short_name' => 'IT 101',
            'term_id' => '2026-1',
        ]);
    }

    public function test_importer_detects_course_csv_submitted_from_account_form(): void
    {
        [$admin] = $this->adminAndDepartment();
        $csv = "course_id,short_name,long_name,status,term_id\n".
            "IT102,IT 102,Computer Programming,active,2026-1\n";
        $file = UploadedFile::fake()->createWithContent('course_creation.csv', $csv);

        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'account_creation',
            'csv_file' => $file,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('course_creations', [
            'course_id' => 'IT102',
            'long_name' => 'Computer Programming',
        ]);
    }

    public function test_bulk_import_accepts_account_and_course_csv_files_together(): void
    {
        [$admin] = $this->adminAndDepartment();
        $accountFile = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "user_id,login_id,first_name,last_name,full_name,sortable_name,short_name,email,status\n".
            "U-300,test.login,Test,User,Test User,\"User, Test\",Test,test@test.local,active\n",
        );
        $courseFile = UploadedFile::fake()->createWithContent(
            'courses.csv',
            "course_id,short_name,long_name,status,term_id\n".
            "IT103,IT 103,Data Structures,active,2026-1\n",
        );

        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'account_creation',
            'csv_files' => [$accountFile, $courseFile],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('account_creations', ['user_id' => 'U-300']);
        $this->assertDatabaseHas('course_creations', ['course_id' => 'IT103']);
    }

    public function test_student_enrollment_import_appears_in_student_directory(): void
    {
        [$admin, $department] = $this->adminAndDepartment();
        $section = Section::query()->create([
            'section_name' => 'BSIT 2A',
            'department_id' => $department->id,
            'year_level' => 2,
        ]);
        $accountFile = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "user_id,login_id,first_name,last_name,full_name,sortable_name,short_name,email,status\n".
            "U-400,student.login,Student,Example,Student Example,\"Example, Student\",Student,student400@test.local,active\n",
        );
        $courseFile = UploadedFile::fake()->createWithContent(
            'courses.csv',
            "course_id,short_name,long_name,status,term_id\n".
            "IT201,IT 201,Object-Oriented Programming,active,2026-1\n",
        );
        $enrollmentFile = UploadedFile::fake()->createWithContent(
            'enrollments.csv',
            "course_id,section_id,user_id,status,role\n".
            "IT201,{$section->id},U-400,active,student\n",
        );

        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'account_creation',
            'csv_files' => [$accountFile, $courseFile, $enrollmentFile],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('student_enrollments', [
            'course_id' => 'IT201',
            'section_id' => (string) $section->id,
            'user_id' => 'U-400',
            'status' => 'active',
            'role' => 'student',
        ]);
        $this->actingAs($admin)->get(route('admin.students'))
            ->assertOk()
            ->assertSee('Student Example')
            ->assertSee('CCS')
            ->assertSee('Object-Oriented Programming');
    }

    public function test_student_enrollment_allows_a_blank_section_id(): void
    {
        [$admin] = $this->adminAndDepartment();
        $file = UploadedFile::fake()->createWithContent(
            'enrollments.csv',
            "course_id,section_id,user_id,status,role\n".
            "IT202,,U-500,active,student\n",
        );

        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'student_enrollment',
            'csv_files' => [$file],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('student_enrollments', [
            'course_id' => 'IT202',
            'section_id' => null,
            'user_id' => 'U-500',
        ]);
    }

    public function test_imported_student_enrollment_can_be_edited(): void
    {
        [$admin, $department] = $this->adminAndDepartment();
        $section = Section::query()->create(['section_name' => 'BSIT 3A', 'department_id' => $department->id]);
        \App\Models\AccountCreation::query()->create([
            'user_id' => 'U-600',
            'login_id' => 'u600',
            'full_name' => 'Old Name',
            'email' => 'old600@test.local',
            'status' => 'active',
        ]);
        \App\Models\CourseCreation::query()->create([
            'course_id' => 'IT301',
            'short_name' => 'IT 301',
            'long_name' => 'Systems Analysis',
            'status' => 'active',
            'term_id' => '2026-1',
        ]);
        $enrollment = \App\Models\StudentEnrollment::query()->create([
            'course_id' => 'IT301',
            'section_id' => null,
            'user_id' => 'U-600',
            'status' => 'active',
            'role' => 'student',
        ]);

        $this->actingAs($admin)->put(route('admin.students.imported.update', $enrollment), [
            'full_name' => 'Updated Name',
            'email' => 'updated600@test.local',
            'section_id' => (string) $section->id,
            'course_id' => 'IT301',
            'status' => 'inactive',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('account_creations', ['user_id' => 'U-600', 'full_name' => 'Updated Name']);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'section_id' => (string) $section->id,
            'status' => 'inactive',
        ]);
    }

    public function test_faculty_enrollment_import_appears_below_existing_faculty(): void
    {
        [$admin, $department] = $this->adminAndDepartment();
        $section = Section::query()->create(['section_name' => 'BSIT 4A', 'department_id' => $department->id]);
        $accountFile = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "user_id,login_id,first_name,last_name,full_name,sortable_name,short_name,email,status\n".
            "F-700,faculty.login,Faculty,Example,Faculty Example,\"Example, Faculty\",Faculty,faculty700@test.local,active\n",
        );
        $courseFile = UploadedFile::fake()->createWithContent(
            'courses.csv',
            "course_id,short_name,long_name,status,term_id\n".
            "IT401,IT 401,Capstone Project,active,2026-1\n",
        );
        $enrollmentFile = UploadedFile::fake()->createWithContent(
            'faculty-enrollments.csv',
            "course_id,section_id,user_id,status,role\n".
            "IT401,{$section->id},F-700,active,teacher\n",
        );

        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'account_creation',
            'csv_files' => [$accountFile, $courseFile, $enrollmentFile],
        ])->assertSessionHas('success');

        $this->actingAs($admin)->get(route('admin.faculty'))
            ->assertOk()
            ->assertSee('Faculty Example')
            ->assertSee('CCS')
            ->assertSee('Capstone Project')
            ->assertSee('imported-faculty-modal-', false);
    }

    public function test_directory_search_filters_imported_student_and_faculty_records(): void
    {
        [$admin] = $this->adminAndDepartment();

        foreach ([
            ['U-SEARCH-1', 'student.one', 'Matching Student', 'student-match@test.local', 'student'],
            ['U-SEARCH-2', 'student.two', 'Hidden Student', 'student-hidden@test.local', 'student'],
            ['F-SEARCH-1', 'faculty.one', 'Matching Faculty', 'faculty-match@test.local', 'teacher'],
            ['F-SEARCH-2', 'faculty.two', 'Hidden Faculty', 'faculty-hidden@test.local', 'teacher'],
        ] as [$userId, $loginId, $name, $email, $role]) {
            \App\Models\AccountCreation::query()->create([
                'user_id' => $userId,
                'login_id' => $loginId,
                'full_name' => $name,
                'email' => $email,
                'status' => 'active',
            ]);
            \App\Models\StudentEnrollment::query()->create([
                'course_id' => 'SEARCH-COURSE',
                'user_id' => $userId,
                'status' => 'active',
                'role' => $role,
            ]);
        }

        $this->actingAs($admin)->get(route('admin.students', ['search' => 'student-match@test.local']))
            ->assertOk()
            ->assertSee('Matching Student')
            ->assertDontSee('Hidden Student');

        $this->actingAs($admin)->get(route('admin.faculty', ['search' => 'F-SEARCH-1']))
            ->assertOk()
            ->assertSee('Matching Faculty')
            ->assertDontSee('Hidden Faculty');
    }

    public function test_reimporting_account_creation_csv_updates_existing_accounts(): void
    {
        [$admin] = $this->adminAndDepartment();
        $firstFile = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "user_id,login_id,first_name,last_name,full_name,sortable_name,short_name,email,status\n".
            "U-200,old.login,Grace,Hopper,Grace Hopper,\"Hopper, Grace\",Grace,old@test.local,active\n",
        );
        $updatedFile = UploadedFile::fake()->createWithContent(
            'accounts-updated.csv',
            "user_id,login_id,first_name,last_name,full_name,sortable_name,short_name,email,status\n".
            "U-200,new.login,Grace,Hopper,Grace Hopper,\"Hopper, Grace\",Grace,new@test.local,inactive\n",
        );

        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'account_creation',
            'csv_file' => $firstFile,
        ])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.directory.import'), [
            'type' => 'account_creation',
            'csv_file' => $updatedFile,
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('account_creations', 1);
        $this->assertDatabaseHas('account_creations', [
            'user_id' => 'U-200',
            'login_id' => 'new.login',
            'email' => 'new@test.local',
            'status' => 'inactive',
        ]);
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
