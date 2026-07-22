<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    public function rules(): array
    {
        $faculty = $this->route('faculty');

        return [
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('faculty')->ignore($faculty)],
            'faculty_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('faculty')->ignore($faculty)],
            'department_id' => ['required', 'exists:departments,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
