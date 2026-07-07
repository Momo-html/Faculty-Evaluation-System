<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            [
                'department_name' => 'Bachelor of Arts in Political Science',
                'code' => 'BA-POLS',
            ],
            [
                'department_name' => 'Bachelor of Arts in Communication',
                'code' => 'BA-COMM',
            ],
            [
                'department_name' => 'Bachelor of Science in Psychology',
                'code' => 'BS-PSYCH',
            ],
            [
                'department_name' => 'Bachelor of Science in Medical Technology',
                'code' => 'BS-MT',
            ],
            [
                'department_name' => 'Bachelor of Science in Information Technology',
                'code' => 'BS-IT',
            ],
            [
                'department_name' => 'Bachelor of Science in Accountancy',
                'code' => 'BS-A',
            ],
            [
                'department_name' => 'Bachelor of Science in Accounting Information System',
                'code' => 'BS-AIS',
            ],
            [
                'department_name' => 'Bachelor of Science in Business Administration',
                'code' => 'BS-BA',
            ],
            [
                'department_name' => 'Bachelor of Science in Hospitality Management',
                'code' => 'BS-HM',
            ],
            [
                'department_name' => 'Bachelor of Science in Tourism Management',
                'code' => 'BS-TM',
            ],
        ] as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                $department,
            );
        }
    }
}
