<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class FrontendDemoData
{
    /**
     * @return array<string, mixed>
     */
    public static function for(string $view): array
    {
        $departments = self::departments();
        $sections = self::sections();
        $subjects = self::subjects($departments);
        $faculty = self::faculty();
        $mappings = self::mappings();
        $students = self::students();
        $forms = self::forms();

        return match ($view) {
            'admin.dashboard' => [
                'deptData' => collect([
                    self::row(['code' => 'CCS', 'student_count' => 124, 'faculty_count' => 12]),
                    self::row(['code' => 'CBA', 'student_count' => 98, 'faculty_count' => 9]),
                    self::row(['code' => 'CAS', 'student_count' => 76, 'faculty_count' => 7]),
                ]),
                'velocityLabels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'velocityData' => [18, 24, 21, 34, 42, 31, 45],
                'activeForm' => $forms->first(),
                'lowParticipation' => collect([
                    self::row(['section_name' => 'BSIT 3A', 'rate' => 48]),
                    self::row(['section_name' => 'BSBA 2B', 'rate' => 52]),
                ]),
                'totalPopulation' => 298,
                'totalFaculty' => 28,
                'participationRate' => 72.4,
                'totalResponses' => 216,
                'dailyAverage' => 31,
                'daysUntilTarget' => 4,
                'projectedDate' => Carbon::now()->addDays(4)->format('M d, Y'),
                'facultyReadiness' => collect([
                    self::row(['id' => 1, 'faculty_name' => 'Prof. Maria Santos', 'total_received' => 86, 'total_expected' => 96, 'rate' => 89]),
                    self::row(['id' => 2, 'faculty_name' => 'Prof. Daniel Reyes', 'total_received' => 61, 'total_expected' => 88, 'rate' => 69]),
                    self::row(['id' => 3, 'faculty_name' => 'Prof. Anne Cruz', 'total_received' => 72, 'total_expected' => 80, 'rate' => 90]),
                ]),
            ],
            'admin.faculty' => [
                'departments' => $departments,
                'faculty' => $faculty,
            ],
            'admin.forms' => [
                'allForms' => $forms,
            ],
            'admin.mapping' => [
                'departments' => $departments,
                'sections' => $sections,
                'allSubjects' => $subjects,
                'allFaculty' => $faculty,
                'mappings' => $mappings,
            ],
            'admin.security' => [
                'logs' => self::logs(),
            ],
            'admin.sentiment' => [
                'feedbacks' => collect([
                    'Prof. Maria Santos' => collect([
                        self::row(['subject_code' => 'IT 101', 'subject_name' => 'Introduction to Computing', 'respondent_count' => 42, 'mean_score' => 4.62, 'adjectival_rating' => 'Outstanding']),
                        self::row(['subject_code' => 'IT 204', 'subject_name' => 'Web Systems', 'respondent_count' => 37, 'mean_score' => 4.31, 'adjectival_rating' => 'Very Satisfactory']),
                    ]),
                    'Prof. Daniel Reyes' => collect([
                        self::row(['subject_code' => 'GE 101', 'subject_name' => 'Purposive Communication', 'respondent_count' => 29, 'mean_score' => 3.86, 'adjectival_rating' => 'Satisfactory']),
                    ]),
                ]),
            ],
            'admin.students' => [
                'departments' => $departments,
                'sections' => $sections,
                'students' => $students,
                'mappings' => $mappings,
            ],
            'admin.users' => [
                'users' => collect([
                    self::row(['faculty_id' => 'ADM-001', 'name' => 'Aldwin Admin', 'email' => 'admin@example.com', 'role' => 'admin', 'department' => 'CCS']),
                    self::row(['faculty_id' => 'SUP-001', 'name' => 'System Owner', 'email' => 'superadmin@example.com', 'role' => 'superadmin', 'department' => 'All Departments']),
                    self::row(['faculty_id' => '2023-0001', 'name' => 'Juan Dela Cruz', 'email' => 'student@example.com', 'role' => 'student', 'department' => 'CCS']),
                ]),
            ],
            'superadmin.dashboard' => [
                'stats' => [
                    'total_admins' => 3,
                    'total_students' => 298,
                    'total_evaluations' => 216,
                ],
                'admins' => collect([
                    self::row(['id' => 1, 'faculty_id' => 'ADM-001', 'name' => 'Aldwin Admin', 'email' => 'admin@example.com', 'department' => 'CCS']),
                    self::row(['id' => 2, 'faculty_id' => 'ADM-002', 'name' => 'Mika Santos', 'email' => 'mika.santos@feucavite.edu.ph', 'department' => 'CBA']),
                ]),
            ],
            'user.home' => [
                'availableEvaluations' => collect([
                    self::row(['mapping_id' => 1, 'subject_code' => 'IT 101', 'subject_name' => 'Introduction to Computing', 'faculty_name' => 'Prof. Maria Santos']),
                    self::row(['mapping_id' => 2, 'subject_code' => 'GE 101', 'subject_name' => 'Purposive Communication', 'faculty_name' => 'Prof. Daniel Reyes']),
                    self::row(['mapping_id' => 3, 'subject_code' => 'IT 204', 'subject_name' => 'Web Systems', 'faculty_name' => 'Prof. Anne Cruz']),
                ]),
                'completedEvaluations' => [2],
            ],
            'user.eval-form' => [
                'evaluation' => self::row([
                    'mapping_id' => 1,
                    'subject_code' => 'IT 101',
                    'subject_name' => 'Introduction to Computing',
                    'faculty_name' => 'Prof. Maria Santos',
                ]),
                'questions' => collect([
                    self::row(['id' => 1, 'question_text' => 'The instructor explains lessons clearly and uses examples that are easy to follow.', 'type' => 'Scale']),
                    self::row(['id' => 2, 'question_text' => 'The instructor starts and ends classes on time.', 'type' => 'Scale']),
                    self::row(['id' => 3, 'question_text' => 'What feedback would help this instructor improve the class?', 'type' => 'Text']),
                ]),
            ],
            'admin.reports.faculty_pdf' => [
                'school_year' => '2025-2026',
                'semester' => '1st Semester',
                'faculty' => self::row(['name' => 'Prof. Maria Santos']),
                'date' => Carbon::now()->format('F d, Y'),
                'totalExpected' => 96,
                'totalReceived' => 86,
                'rate' => 89.6,
            ],
            default => [],
        };
    }

    private static function row(array $attributes): object
    {
        return (object) $attributes;
    }

    private static function departments()
    {
        return collect([
            self::row(['id' => 1, 'code' => 'CCS', 'full_name' => 'College of Computer Studies']),
            self::row(['id' => 2, 'code' => 'CBA', 'full_name' => 'College of Business Administration']),
            self::row(['id' => 3, 'code' => 'CAS', 'full_name' => 'College of Arts and Sciences']),
        ]);
    }

    private static function sections()
    {
        return collect([
            self::row(['id' => 1, 'section_name' => 'BSIT 3A', 'department_id' => 1, 'department_name' => 'College of Computer Studies']),
            self::row(['id' => 2, 'section_name' => 'BSIT 3B', 'department_id' => 1, 'department_name' => 'College of Computer Studies']),
            self::row(['id' => 3, 'section_name' => 'BSBA 2B', 'department_id' => 2, 'department_name' => 'College of Business Administration']),
        ]);
    }

    private static function subjects($departments)
    {
        return collect([
            self::row(['id' => 1, 'subject_code' => 'IT 101', 'subject_name' => 'Introduction to Computing', 'type' => 'major', 'department' => $departments->firstWhere('code', 'CCS')]),
            self::row(['id' => 2, 'subject_code' => 'IT 204', 'subject_name' => 'Web Systems', 'type' => 'major', 'department' => $departments->firstWhere('code', 'CCS')]),
            self::row(['id' => 3, 'subject_code' => 'GE 101', 'subject_name' => 'Purposive Communication', 'type' => 'minor', 'department' => null]),
        ]);
    }

    private static function faculty()
    {
        return collect([
            self::row(['id' => 1, 'employee_id' => 'FAC-001', 'name' => 'Prof. Maria Santos', 'email' => 'maria.santos@feucavite.edu.ph', 'department_id' => 1, 'department_name' => 'College of Computer Studies', 'department_code' => 'CCS', 'dept_code' => 'CCS']),
            self::row(['id' => 2, 'employee_id' => 'FAC-002', 'name' => 'Prof. Daniel Reyes', 'email' => 'daniel.reyes@feucavite.edu.ph', 'department_id' => 3, 'department_name' => 'College of Arts and Sciences', 'department_code' => 'CAS', 'dept_code' => 'CAS']),
            self::row(['id' => 3, 'employee_id' => 'FAC-003', 'name' => 'Prof. Anne Cruz', 'email' => 'anne.cruz@feucavite.edu.ph', 'department_id' => 1, 'department_name' => 'College of Computer Studies', 'department_code' => 'CCS', 'dept_code' => 'CCS']),
        ]);
    }

    private static function mappings()
    {
        return collect([
            self::row(['id' => 1, 'department_id' => 1, 'department_name' => 'College of Computer Studies', 'subject_code' => 'IT 101', 'subject_name' => 'Introduction to Computing', 'faculty_name' => 'Prof. Maria Santos', 'section_name' => 'BSIT 3A', 'semester' => '1st Semester']),
            self::row(['id' => 2, 'department_id' => 3, 'department_name' => 'College of Arts and Sciences', 'subject_code' => 'GE 101', 'subject_name' => 'Purposive Communication', 'faculty_name' => 'Prof. Daniel Reyes', 'section_name' => 'BSIT 3A', 'semester' => '1st Semester']),
            self::row(['id' => 3, 'department_id' => 1, 'department_name' => 'College of Computer Studies', 'subject_code' => 'IT 204', 'subject_name' => 'Web Systems', 'faculty_name' => 'Prof. Anne Cruz', 'section_name' => 'BSIT 3B', 'semester' => '1st Semester']),
        ]);
    }

    private static function students()
    {
        return collect([
            self::row([
                'id' => 1,
                'faculty_id' => '2023-0001',
                'name' => 'Juan Dela Cruz',
                'department' => 'CCS',
                'department_id' => 1,
                'department_name' => 'College of Computer Studies',
                'section_id' => 1,
                'primary_section_name' => 'BSIT 3A',
                'enrolled_subjects' => collect([
                    self::row(['id' => 1, 'subject_code' => 'IT 101', 'section_name' => 'BSIT 3A']),
                    self::row(['id' => 2, 'subject_code' => 'GE 101', 'section_name' => 'BSIT 3A']),
                ]),
            ]),
            self::row([
                'id' => 2,
                'faculty_id' => '2023-0002',
                'name' => 'Lia Mendoza',
                'department' => 'CCS',
                'department_id' => 1,
                'department_name' => 'College of Computer Studies',
                'section_id' => 2,
                'primary_section_name' => 'BSIT 3B',
                'enrolled_subjects' => collect([
                    self::row(['id' => 3, 'subject_code' => 'IT 204', 'section_name' => 'BSIT 3B']),
                ]),
            ]),
        ]);
    }

    private static function forms()
    {
        return collect([
            self::row(['id' => 1, 'school_year' => '2025-2026', 'semester' => '1st Semester', 'open_at' => Carbon::now()->subDays(7)->format('Y-m-d H:i:s'), 'close_at' => Carbon::now()->addDays(14)->format('Y-m-d H:i:s'), 'is_active' => true]),
            self::row(['id' => 2, 'school_year' => '2024-2025', 'semester' => '2nd Semester', 'open_at' => Carbon::now()->subMonths(5)->format('Y-m-d H:i:s'), 'close_at' => Carbon::now()->subMonths(4)->format('Y-m-d H:i:s'), 'is_active' => false]),
        ]);
    }

    private static function logs(): LengthAwarePaginator
    {
        $items = collect([
            self::row(['created_at' => Carbon::now()->subMinutes(15), 'user' => self::row(['name' => 'Aldwin Admin']), 'action' => 'LOGIN', 'resource' => 'Admin Portal', 'description' => 'Successful admin sign-in.', 'ip_address' => '127.0.0.1']),
            self::row(['created_at' => Carbon::now()->subHours(2), 'user' => self::row(['name' => 'System']), 'action' => 'EXPORT', 'resource' => 'Faculty Report', 'description' => 'Generated faculty readiness PDF.', 'ip_address' => '127.0.0.1']),
            self::row(['created_at' => Carbon::now()->subDay(), 'user' => self::row(['name' => 'Aldwin Admin']), 'action' => 'UPDATE', 'resource' => 'Evaluation Form', 'description' => 'Updated active evaluation period.', 'ip_address' => '127.0.0.1']),
        ]);

        return new LengthAwarePaginator($items, $items->count(), 10, 1, [
            'path' => request()->url(),
        ]);
    }
}
