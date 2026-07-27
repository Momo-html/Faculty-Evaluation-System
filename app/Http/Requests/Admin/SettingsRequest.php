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
        $rules = [
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
            'department_report_title' => ['required', 'string', 'max:255'],
            'department_report_intro' => ['nullable', 'string', 'max:1000'],
            'department_report_footer_text' => ['nullable', 'string', 'max:500'],
            'individual_report_title' => ['required', 'string', 'max:255'],
            'individual_report_intro' => ['nullable', 'string', 'max:1000'],
            'individual_report_footer_text' => ['nullable', 'string', 'max:500'],
            'admin_remarks_label' => ['required', 'string', 'max:100'],
            'prepared_by_label' => ['required', 'string', 'max:100'],
            'signature_label' => ['required', 'string', 'max:100'],
            'rating_scale_max' => ['required', 'numeric', 'min:1', 'max:10'],
            'performance_excellent_min' => ['required', 'numeric', 'min:0', 'max:10'],
            'performance_excellent_max' => ['required', 'numeric', 'min:0', 'max:10'],
            'performance_very_satisfactory_min' => ['required', 'numeric', 'min:0', 'max:10'],
            'performance_very_satisfactory_max' => ['required', 'numeric', 'min:0', 'max:10'],
            'performance_needs_improvement_min' => ['required', 'numeric', 'min:0', 'max:10'],
            'performance_needs_improvement_max' => ['required', 'numeric', 'min:0', 'max:10'],
            'performance_poor_min' => ['required', 'numeric', 'min:0', 'max:10'],
            'performance_poor_max' => ['required', 'numeric', 'min:0', 'max:10'],
            'minimum_reliable_responses' => ['required', 'integer', 'min:1', 'max:1000'],
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

        foreach (\App\Support\SettingsSupport::departmentPdfBooleanKeys() as $key) {
            $rules[$key] = ['nullable', 'boolean'];
        }

        foreach (\App\Support\SettingsSupport::individualPdfBooleanKeys() as $key) {
            $rules[$key] = ['nullable', 'boolean'];
        }

        $sectionKeys = [
            'general' => [
                'school_name',
                'portal_name',
                'system_name',
                'school_address',
                'school_email',
                'school_contact_number',
                'footer_text',
            ],
            'branding' => [
                'school_logo',
                'header_logo',
                'sidebar_logo',
                'login_logo',
                'favicon',
                'reset_school_logo',
                'reset_header_logo',
                'reset_sidebar_logo',
                'reset_login_logo',
                'reset_favicon',
            ],
            'evaluation' => [
                'evaluation_status',
                'current_academic_year',
                'current_semester',
                'evaluation_start_date',
                'evaluation_deadline',
                'allow_late_submissions',
                'allow_one_submission_only',
                'allow_student_edit_submissions',
                'default_evaluation_instructions',
                'default_evaluation_form_id',
            ],
            'reports' => [
                'allow_pdf_export',
                'report_visibility',
                'include_school_logo_pdf',
                'include_school_name_pdf',
                'include_generated_date_pdf',
                'include_prepared_by_pdf',
                'include_signature_line_pdf',
                'default_report_title',
                'department_report_title',
                'department_report_intro',
                'department_report_footer_text',
                'individual_report_title',
                'individual_report_intro',
                'individual_report_footer_text',
                'admin_remarks_label',
                'prepared_by_label',
                'signature_label',
                ...\App\Support\SettingsSupport::departmentPdfBooleanKeys(),
                ...\App\Support\SettingsSupport::individualPdfBooleanKeys(),
            ],
            'performance' => [
                'rating_scale_max',
                'performance_excellent_min',
                'performance_excellent_max',
                'performance_very_satisfactory_min',
                'performance_very_satisfactory_max',
                'performance_needs_improvement_min',
                'performance_needs_improvement_max',
                'performance_poor_min',
                'performance_poor_max',
                'minimum_reliable_responses',
            ],
            'student' => [
                'student_evaluation_page_title',
                'student_evaluation_instructions',
                'show_deadline_to_students',
                'show_progress_bar',
                'show_required_question_indicator',
                'show_confirmation_before_submit',
                'thank_you_message',
            ],
            'security' => [
                'session_timeout',
                'password_min_length',
                'strong_password_required',
                'login_attempt_limit',
                'account_lock_duration',
                'maintenance_mode',
            ],
        ];

        $section = (string) $this->input('section', 'general');
        $keys = $sectionKeys[$section] ?? array_keys($rules);

        return [
            'section' => ['nullable', 'in:general,branding,evaluation,reports,performance,student,security'],
        ] + collect($rules)->only($keys)->all();
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
