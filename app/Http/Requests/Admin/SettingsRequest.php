<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'superadmin'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'evaluation_status' => ['required', 'in:open,closed'],
            'current_semester' => ['required', 'string', 'max:50'],
            'current_academic_year' => ['required', 'string', 'max:20'],
            'allow_pdf_export' => ['nullable', 'boolean'],
            'evaluation_deadline' => ['nullable', 'date'],
            'report_visibility' => ['required', 'in:admins_only,faculty_visible,closed'],
            'system_name' => ['required', 'string', 'max:255'],
        ];
    }
}
