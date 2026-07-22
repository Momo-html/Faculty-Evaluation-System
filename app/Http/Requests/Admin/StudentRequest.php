<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'student_number' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($student)],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($student)],
            'department_id' => ['required', 'exists:departments,id'],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where(fn ($query) => $query->where('department_id', $this->integer('department_id')))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
