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
            'school_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'header_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sidebar_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'login_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'reset_school_logo' => ['nullable', 'boolean'],
            'reset_header_logo' => ['nullable', 'boolean'],
            'reset_sidebar_logo' => ['nullable', 'boolean'],
            'reset_login_logo' => ['nullable', 'boolean'],
            'reset_favicon' => ['nullable', 'boolean'],
            'school_name' => ['required', 'string', 'max:255'],
            'portal_name' => ['required', 'string', 'max:255'],
            'system_name' => ['required', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_email' => ['nullable', 'email', 'max:255'],
            'school_contact_number' => ['nullable', 'string', 'max:50'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'evaluation_status' => ['required', 'in:open,closed'],
            'current_academic_year' => ['required', 'string', 'max:20'],
            'current_semester' => ['required', 'string', 'max:50'],
            'evaluation_start_date' => ['nullable', 'date'],
            'evaluation_deadline' => ['nullable', 'date'],
            'allow_late_submissions' => ['nullable', 'boolean'],
            'allow_one_submission_only' => ['nullable', 'boolean'],
            'allow_student_edit_submissions' => ['nullable', 'boolean'],
            'default_evaluation_instructions' => ['nullable', 'string', 'max:3000'],
            'default_evaluation_form_id' => ['nullable', 'exists:evaluation_forms,id'],
            'allow_pdf_export' => ['nullable', 'boolean'],
            'report_visibility' => ['required', 'in:admins_only,faculty_visible,closed'],
            'include_school_logo_pdf' => ['nullable', 'boolean'],
            'include_school_name_pdf' => ['nullable', 'boolean'],
            'include_generated_date_pdf' => ['nullable', 'boolean'],
            'include_prepared_by_pdf' => ['nullable', 'boolean'],
            'include_signature_line_pdf' => ['nullable', 'boolean'],
            'default_report_title' => ['required', 'string', 'max:255'],
            'student_evaluation_page_title' => ['required', 'string', 'max:255'],
            'student_evaluation_instructions' => ['nullable', 'string', 'max:3000'],
            'show_deadline_to_students' => ['nullable', 'boolean'],
            'show_progress_bar' => ['nullable', 'boolean'],
            'show_required_question_indicator' => ['nullable', 'boolean'],
            'show_confirmation_before_submit' => ['nullable', 'boolean'],
            'thank_you_message' => ['required', 'string', 'max:500'],
            'session_timeout' => ['required', 'integer', 'min:15', 'max:1440'],
            'password_min_length' => ['required', 'integer', 'min:8', 'max:32'],
            'strong_password_required' => ['nullable', 'boolean'],
            'login_attempt_limit' => ['required', 'integer', 'min:3', 'max:20'],
            'account_lock_duration' => ['required', 'integer', 'min:5', 'max:1440'],
            'maintenance_mode' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'school_logo' => 'school logo',
            'header_logo' => 'header logo',
            'sidebar_logo' => 'sidebar logo',
            'login_logo' => 'login page logo',
            'favicon' => 'favicon',
        ];
    }
}
