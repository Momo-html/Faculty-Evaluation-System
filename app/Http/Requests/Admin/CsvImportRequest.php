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
            'type' => ['required', Rule::in(['faculty', 'student', 'account_creation', 'course_creation', 'student_enrollment'])],
            'csv_file' => ['nullable', 'required_without:csv_files', 'file', 'mimes:csv,txt', 'max:5120'],
            'csv_files' => ['nullable', 'required_without:csv_file', 'array', 'min:1'],
            'csv_files.*' => ['file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please choose a CSV file to import.',
            'csv_file.file' => 'The selected upload is not a valid file.',
            'csv_file.mimes' => 'Wrong file type. Please upload a CSV file.',
            'csv_file.max' => 'The CSV file must not be larger than 5 MB.',
            'csv_files.required_without' => 'Please choose one or more CSV files to import.',
            'csv_files.*.file' => 'One of the selected uploads is not a valid file.',
            'csv_files.*.mimes' => 'Every selected file must be a CSV file.',
            'csv_files.*.max' => 'Each CSV file must not be larger than 5 MB.',
        ];
    }
}
