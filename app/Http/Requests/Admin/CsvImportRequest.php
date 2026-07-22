<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['faculty', 'student'])],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
