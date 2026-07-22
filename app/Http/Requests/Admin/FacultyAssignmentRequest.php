<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class FacultyAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', 'string', 'max:50'],
        ];
    }
}
