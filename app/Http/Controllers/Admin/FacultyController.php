<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use App\Support\FrontendDemoData;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FacultyController extends Controller
{
    public function __invoke(): View
    {
        if (! Schema::hasTable('faculty')) {
            return view('admin.faculty', FrontendDemoData::for('admin.faculty'));
        }

        return view('admin.faculty', [
            'departments' => Department::query()->orderBy('department_name')->get(),
            'faculty' => Faculty::query()->with('department')->orderBy('faculty_name')->paginate(15),
        ]);
    }
}
