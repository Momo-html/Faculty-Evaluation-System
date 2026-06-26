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
            ['department_name' => 'College of Computer Studies', 'code' => 'CCS'],
            ['department_name' => 'College of Business', 'code' => 'CB'],
        ] as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                $department,
            );
        }
    }
}
