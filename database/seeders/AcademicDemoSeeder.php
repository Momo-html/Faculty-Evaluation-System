<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectMapping;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::query()->firstOrCreate(
            ['code' => 'CCS'],
            ['department_name' => 'College of Computer Studies'],
        );

        $section = Section::query()->firstOrCreate(
            [
                'section_name' => 'BSIT 3A',
                'department_id' => $department->id,
                'year_level' => 3,
            ],
        );

        $faculty = Faculty::query()->firstOrCreate(
            ['email' => 'maria.santos@feucavite.edu.ph'],
            [
                'faculty_name' => 'Prof. Maria Santos',
                'department_id' => $department->id,
                'status' => 'active',
            ],
        );

        $subject = Subject::query()->firstOrCreate(
            ['subject_code' => 'IT 101'],
            [
                'subject_name' => 'Introduction to Computing',
                'department_id' => $department->id,
                'units' => 3,
            ],
        );

        $mapping = SubjectMapping::query()->firstOrCreate(
            [
                'faculty_id' => $faculty->id,
                'subject_id' => $subject->id,
                'section_id' => $section->id,
                'school_year' => '2025-2026',
                'semester' => '1st Semester',
            ],
        );

        $student = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->orderBy('id')
            ->first();

        if ($student) {
            DB::table('student_subjects')->updateOrInsert(
                [
                    'user_id' => $student->id,
                    'subject_mapping_id' => $mapping->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
