<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StudentAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    public function rules(): array
    {
        return ['subject_mapping_id' => ['required', 'exists:subject_mappings,id']];
    }
}
