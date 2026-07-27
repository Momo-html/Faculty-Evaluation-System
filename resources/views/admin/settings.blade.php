@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/settings.css') }}">
@endpush

@php
    $activeSection = $activeSection ?? 'general';
    $settingSections = collect($settingSections ?? []);
    $currentSection = $settingSections[$activeSection] ?? ['label' => 'Settings', 'description' => 'Manage system configuration.'];
    $schoolLogoPath = $settings['school_logo_path'] ?? null;
    $imageFields = [
        'school_logo' => [
            'label' => 'Main School Logo',
            'key' => 'school_logo_path',
            'dimensions' => 'Recommended: square image, 320 x 320 px or larger.',
            'note' => 'Used as the default logo when other locations do not have a custom image.',
        ],
        'header_logo' => [
            'label' => 'Header Logo',
            'key' => 'header_logo_path',
            'dimensions' => 'Recommended: wide image, 360 x 96 px.',
            'note' => 'Appears in the top navigation bar.',
        ],
        'sidebar_logo' => [
            'label' => 'Sidebar Logo',
            'key' => 'sidebar_logo_path',
            'dimensions' => 'Recommended: compact image, 280 x 120 px.',
            'note' => 'Appears in the admin sidebar.',
        ],
        'login_logo' => [
            'label' => 'Login Page Logo',
            'key' => 'login_logo_path',
            'dimensions' => 'Recommended: clear logo, 360 x 160 px.',
            'note' => 'Appears on login screens.',
        ],
        'favicon' => [
            'label' => 'Favicon',
            'key' => 'favicon_path',
            'dimensions' => 'Recommended: square icon, 64 x 64 px.',
            'note' => 'Appears in the browser tab.',
        ],
    ];
    $pdfToggleGroups = [
        'individual' => [
            'Report Header' => [
                ['individual_pdf_school_logo', 'School Logo', 'Show the school logo at the top of the report.'],
                ['individual_pdf_school_name', 'School Name', 'Show the official school name.'],
                ['individual_pdf_system_name', 'Portal/System Name', 'Show the portal or system name.'],
                ['individual_pdf_report_title', 'Report Title', 'Show the configured individual report title.'],
            ],
            'Faculty and Class Information' => [
                ['individual_pdf_faculty_name', 'Faculty Name', 'Show the evaluated professor.'],
                ['individual_pdf_department', 'Department', 'Show the professor department.'],
                ['individual_pdf_academic_year', 'Academic Year', 'Show the academic year used for the report.'],
                ['individual_pdf_semester', 'Semester', 'Show the selected semester.'],
                ['individual_pdf_subject', 'Subject / Course', 'Show subject or course information.'],
                ['individual_pdf_section', 'Section', 'Show class section information.'],
            ],
            'Evaluation Summary' => [
                ['individual_pdf_total_respondents', 'Total Respondents', 'Show the number of submitted evaluations.'],
                ['individual_pdf_overall_average', 'Overall Average Rating', 'Show the computed overall rating.'],
                ['individual_pdf_classification', 'Performance Classification', 'Show the performance label.'],
                ['individual_pdf_reliability_indicator', 'Reliability Indicator', 'Show whether the response count is reliable.'],
            ],
            'Detailed Results' => [
                ['individual_pdf_average_per_question', 'Average Rating per Question', 'Show question-level averages.'],
                ['individual_pdf_average_per_category', 'Average Rating per Category', 'Show category-level averages.'],
            ],
            'Footer and Approval' => [
                ['individual_pdf_admin_remarks', 'Admin Remarks Section', 'Show a blank admin remarks area.'],
                ['individual_pdf_signature_line', 'Prepared By / Signature Line', 'Show the signature area.'],
                ['individual_pdf_footer_text', 'Footer Text', 'Show the configured footer text.'],
            ],
        ],
        'department' => [
            'Report Header' => [
                ['department_pdf_school_logo', 'School Logo', 'Show the school logo at the top of the report.'],
                ['department_pdf_school_name', 'School Name', 'Show the official school name.'],
                ['department_pdf_system_name', 'Portal/System Name', 'Show the portal or system name.'],
                ['department_pdf_report_title', 'Report Title', 'Show the configured department report title.'],
            ],
            'Department Details' => [
                ['department_pdf_department_name', 'Department Name', 'Show the selected department.'],
                ['department_pdf_academic_year', 'Academic Year', 'Show the academic year used for the report.'],
                ['department_pdf_semester', 'Semester', 'Show the selected semester.'],
                ['department_pdf_date_generated', 'Date Generated', 'Show when the report was exported.'],
                ['department_pdf_generated_by', 'Generated By', 'Show the administrator who generated the report.'],
            ],
            'Evaluation Summary' => [
                ['department_pdf_total_faculty', 'Total Faculty Evaluated', 'Show how many faculty are included.'],
                ['department_pdf_total_responses', 'Total Student Responses', 'Show the number of submitted evaluations.'],
                ['department_pdf_overall_average', 'Overall Department Average', 'Show the computed department average.'],
                ['department_pdf_classification', 'Department Performance Classification', 'Show the performance label.'],
            ],
            'Detailed Results' => [
                ['department_pdf_faculty_summary_table', 'Faculty Ranking / Summary Table', 'Show the faculty summary table.'],
                ['department_pdf_average_per_faculty', 'Average Rating per Faculty', 'Show faculty-level averages.'],
                ['department_pdf_average_per_category', 'Average Rating per Category', 'Show category-level averages.'],
                ['department_pdf_interpretation_legend', 'Performance Interpretation Legend', 'Show the rating scale guide.'],
            ],
            'Footer and Approval' => [
                ['department_pdf_signature_line', 'Prepared By / Signature Line', 'Show the signature area.'],
                ['department_pdf_footer_text', 'Footer Text', 'Show the configured footer text.'],
            ],
        ],
    ];
@endphp

@section('content')
<div id="settings" class="settings-page" data-branding-upload-url="{{ route('admin.settings.branding-image') }}" data-branding-csrf="{{ csrf_token() }}">
    <section class="settings-hero">
        <div>
            <p class="settings-eyebrow">System Configuration</p>
            <h1>{{ $currentSection['label'] }}</h1>
            <p>{{ $currentSection['description'] }}</p>
        </div>
    </section>

    <nav class="settings-section-tabs" aria-label="Settings sections">
        @foreach($settingSections as $key => $section)
            <a href="{{ route('admin.settings.section', $key) }}" class="{{ $activeSection === $key ? 'active' : '' }}">
                {{ $section['label'] }}
            </a>
        @endforeach
    </nav>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-danger">
            <strong>Please check this settings page:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($activeSection !== 'profile')
        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="settings-stack">
            @csrf
            <input type="hidden" name="section" value="{{ $activeSection }}">

            @if($activeSection === 'general')
                <section class="settings-card">
                    <div class="settings-card-heading">
                        <div>
                            <h2>General Information</h2>
                            <p>Keep the names and contact details clear across the portal.</p>
                        </div>
                    </div>

                    <div class="settings-grid two">
                        <label>
                            <span>School Name</span>
                            <input type="text" name="school_name" value="{{ old('school_name', $settings['school_name']) }}" required>
                        </label>
                        <label>
                            <span>Portal Name</span>
                            <input type="text" name="portal_name" value="{{ old('portal_name', $settings['portal_name']) }}" required>
                        </label>
                        <label>
                            <span>System Name</span>
                            <input type="text" name="system_name" value="{{ old('system_name', $settings['system_name']) }}" required>
                        </label>
                        <label>
                            <span>School Email</span>
                            <input type="email" name="school_email" value="{{ old('school_email', $settings['school_email']) }}">
                        </label>
                        <label>
                            <span>School Contact Number</span>
                            <input type="text" name="school_contact_number" value="{{ old('school_contact_number', $settings['school_contact_number']) }}">
                        </label>
                        <label>
                            <span>Footer Text</span>
                            <input type="text" name="footer_text" value="{{ old('footer_text', $settings['footer_text']) }}">
                        </label>
                        <label class="settings-full">
                            <span>School Address</span>
                            <textarea name="school_address" rows="3">{{ old('school_address', $settings['school_address']) }}</textarea>
                        </label>
                    </div>
                </section>
            @endif

            @if($activeSection === 'branding')
                <section class="settings-card">
                    <div class="settings-card-heading">
                        <div>
                            <h2>Branding Asset Manager</h2>
                            <p>Select one asset at a time. The panel below changes based on your dropdown choice.</p>
                        </div>
                    </div>

                    <label class="settings-branding-picker">
                        <span>Branding Asset</span>
                        <select data-branding-picker>
                            @foreach($imageFields as $input => $meta)
                                <option value="{{ $input }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </label>

                    @foreach($imageFields as $input => $meta)
                        @php
                            $assetPath = $settings[$meta['key']] ?? null;
                            $usesMainLogo = in_array($input, ['header_logo', 'sidebar_logo', 'login_logo'], true)
                                && (! $assetPath || $assetPath === $schoolLogoPath);
                        @endphp
                        <div class="settings-branding-panel" data-branding-field="{{ $input }}" @if(! $loop->first) hidden @endif>
                            <div class="settings-image-preview" data-branding-preview="{{ $input }}">
                                @if(($portalImage)($meta['key']))
                                    <img src="{{ ($portalImage)($meta['key']) }}" alt="{{ $meta['label'] }} preview">
                                @else
                                    <span>FEU</span>
                                @endif
                            </div>

                            <div class="settings-branding-copy">
                                <h3>{{ $meta['label'] }}</h3>
                                <p>{{ $meta['note'] }}</p>
                                <small>{{ $meta['dimensions'] }}</small>
                                <small>Allowed file types: JPG, PNG, WEBP. Maximum size: 2 MB.</small>

                                @if(in_array($input, ['header_logo', 'sidebar_logo', 'login_logo'], true))
                                    <label class="settings-check">
                                        <input type="checkbox" name="reset_{{ $input }}" value="1" data-branding-reset="{{ $input }}" @checked($usesMainLogo)>
                                        <span>Use the main school logo for this location</span>
                                    </label>
                                @else
                                    <label class="settings-check">
                                        <input type="checkbox" name="reset_{{ $input }}" value="1" data-branding-reset="{{ $input }}">
                                        <span>Reset to default</span>
                                    </label>
                                @endif

                                <small class="settings-upload-status" data-branding-status="{{ $input }}"></small>
                            </div>

                            <div class="settings-branding-actions">
                                <label class="btn-secondary settings-file-button">
                                    Choose Image
                                    <input type="file" name="{{ $input }}" accept=".jpg,.jpeg,.png,.webp">
                                </label>
                                <button type="button" class="btn-secondary" data-branding-choose="{{ $input }}">Replace Image</button>
                                <button type="submit" class="btn-primary">Save Branding</button>
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif

            @if($activeSection === 'evaluation')
                <section class="settings-card">
                    <div class="settings-card-heading">
                        <div>
                            <h2>Evaluation Controls</h2>
                            <p>Control the active evaluation period and student submission rules.</p>
                        </div>
                    </div>

                    <div class="settings-grid two">
                        <label>
                            <span>Evaluation Status</span>
                            <select name="evaluation_status" required>
                                <option value="open" @selected(old('evaluation_status', $settings['evaluation_status']) === 'open')>Open</option>
                                <option value="closed" @selected(old('evaluation_status', $settings['evaluation_status']) === 'closed')>Closed</option>
                            </select>
                        </label>
                        <label>
                            <span>Default Active Evaluation Form</span>
                            <select name="default_evaluation_form_id">
                                <option value="">Use latest active form</option>
                                @foreach($forms as $form)
                                    <option value="{{ $form->id }}" @selected((string) old('default_evaluation_form_id', $settings['default_evaluation_form_id']) === (string) $form->id)>
                                        {{ $form->title }} - {{ $form->school_year }} {{ $form->semester }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Current Academic Year</span>
                            <input type="text" name="current_academic_year" value="{{ old('current_academic_year', $settings['current_academic_year']) }}" required>
                        </label>
                        <label>
                            <span>Current Semester</span>
                            <select name="current_semester" required>
                                <option value="1st Semester" @selected(old('current_semester', $settings['current_semester']) === '1st Semester')>1st Semester</option>
                                <option value="2nd Semester" @selected(old('current_semester', $settings['current_semester']) === '2nd Semester')>2nd Semester</option>
                                <option value="Summer" @selected(old('current_semester', $settings['current_semester']) === 'Summer')>Summer</option>
                            </select>
                        </label>
                        <label>
                            <span>Evaluation Start Date</span>
                            <input type="date" name="evaluation_start_date" value="{{ old('evaluation_start_date', $settings['evaluation_start_date']) }}">
                        </label>
                        <label>
                            <span>Evaluation Deadline</span>
                            <input type="date" name="evaluation_deadline" value="{{ old('evaluation_deadline', $settings['evaluation_deadline']) }}">
                        </label>
                        <label class="settings-full">
                            <span>Default Evaluation Instructions</span>
                            <textarea name="default_evaluation_instructions" rows="4">{{ old('default_evaluation_instructions', $settings['default_evaluation_instructions']) }}</textarea>
                        </label>
                    </div>

                    <div class="settings-toggle-list">
                        @foreach([
                            'allow_late_submissions' => 'Allow late submissions',
                            'allow_one_submission_only' => 'Allow one submission only',
                            'allow_student_edit_submissions' => 'Allow students to edit submitted evaluations',
                        ] as $key => $label)
                            <label class="settings-toggle compact">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($activeSection === 'reports')
                <section class="settings-card">
                    <div class="settings-card-heading">
                        <div>
                            <h2>PDF Reports</h2>
                            <p>Choose report titles, visibility, and the fields shown in exported PDFs.</p>
                        </div>
                    </div>

                    <div class="settings-grid two">
                        <label>
                            <span>Report Visibility</span>
                            <select name="report_visibility" required>
                                <option value="admins_only" @selected(old('report_visibility', $settings['report_visibility']) === 'admins_only')>Admins only</option>
                                <option value="faculty_visible" @selected(old('report_visibility', $settings['report_visibility']) === 'faculty_visible')>Faculty visible</option>
                                <option value="closed" @selected(old('report_visibility', $settings['report_visibility']) === 'closed')>Closed</option>
                            </select>
                        </label>
                        <label>
                            <span>Default Report Title</span>
                            <input type="text" name="default_report_title" value="{{ old('default_report_title', $settings['default_report_title']) }}" required>
                        </label>
                        <label>
                            <span>Department Report Title</span>
                            <input type="text" name="department_report_title" value="{{ old('department_report_title', $settings['department_report_title']) }}" required>
                        </label>
                        <label>
                            <span>Individual Report Title</span>
                            <input type="text" name="individual_report_title" value="{{ old('individual_report_title', $settings['individual_report_title']) }}" required>
                        </label>
                        <label class="settings-full">
                            <span>Department Report Introduction</span>
                            <textarea name="department_report_intro" rows="2">{{ old('department_report_intro', $settings['department_report_intro']) }}</textarea>
                        </label>
                        <label class="settings-full">
                            <span>Individual Report Introduction</span>
                            <textarea name="individual_report_intro" rows="2">{{ old('individual_report_intro', $settings['individual_report_intro']) }}</textarea>
                        </label>
                        <label>
                            <span>Department Report Footer Text</span>
                            <input type="text" name="department_report_footer_text" value="{{ old('department_report_footer_text', $settings['department_report_footer_text']) }}">
                        </label>
                        <label>
                            <span>Individual Report Footer Text</span>
                            <input type="text" name="individual_report_footer_text" value="{{ old('individual_report_footer_text', $settings['individual_report_footer_text']) }}">
                        </label>
                        <label>
                            <span>Prepared By Label</span>
                            <input type="text" name="prepared_by_label" value="{{ old('prepared_by_label', $settings['prepared_by_label']) }}" required>
                        </label>
                        <label>
                            <span>Signature Label</span>
                            <input type="text" name="signature_label" value="{{ old('signature_label', $settings['signature_label']) }}" required>
                        </label>
                        <label>
                            <span>Admin Remarks Label</span>
                            <input type="text" name="admin_remarks_label" value="{{ old('admin_remarks_label', $settings['admin_remarks_label']) }}" required>
                        </label>
                    </div>

                    <div class="settings-toggle-list">
                        @foreach([
                            'allow_pdf_export' => 'Allow PDF downloads',
                            'include_school_logo_pdf' => 'Include school logo in PDF',
                            'include_school_name_pdf' => 'Include school name in PDF header',
                            'include_generated_date_pdf' => 'Include generated date',
                            'include_prepared_by_pdf' => 'Include prepared by',
                            'include_signature_line_pdf' => 'Include signature line',
                        ] as $key => $label)
                            <label class="settings-toggle compact">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="settings-subsection settings-pdf-builder" data-pdf-builder>
                        <div class="settings-pdf-builder-header">
                            <div>
                                <h3>Report content</h3>
                                <p>Choose one report type, then open only the group you want to adjust.</p>
                            </div>
                            <div class="settings-pdf-tabs" role="tablist" aria-label="PDF report type">
                                <button type="button" class="active" data-pdf-tab="individual" role="tab" aria-selected="true">Individual Professor</button>
                                <button type="button" data-pdf-tab="department" role="tab" aria-selected="false">Department</button>
                            </div>
                        </div>

                        @foreach($pdfToggleGroups as $type => $groups)
                            <div class="settings-pdf-panel" data-pdf-panel="{{ $type }}" @if($type === 'department') hidden @endif>
                                @foreach($groups as $groupTitle => $items)
                                    <details class="settings-pdf-group" @if($loop->first) open @endif>
                                        <summary>
                                            <span>{{ $groupTitle }}</span>
                                            <small>{{ count($items) }} options</small>
                                        </summary>
                                        <div class="settings-pdf-toggle-list">
                                            @foreach($items as [$key, $label, $description])
                                                <label class="settings-toggle settings-pdf-toggle">
                                                    <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                                                    <span>
                                                        <strong>{{ $label }}</strong>
                                                        <small>{{ $description }}</small>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($activeSection === 'performance')
                <section class="settings-card">
                    <div class="settings-card-heading">
                        <div>
                            <h2>Performance Scale</h2>
                            <p>Adjust the rating scale, thresholds, and response count used for reliability labels.</p>
                        </div>
                    </div>

                    <div class="settings-grid two">
                        <input type="hidden" name="rating_scale_max" value="4.00">
                        <div class="settings-static-field">
                            <span>Rating Scale</span>
                            <strong>4-point scale only</strong>
                        </div>
                        <label>
                            <span>Minimum Response Count for Reliable Result</span>
                            <input type="number" min="1" max="1000" name="minimum_reliable_responses" value="{{ old('minimum_reliable_responses', $settings['minimum_reliable_responses']) }}" required>
                        </label>
                    </div>

                    <div class="settings-threshold-grid">
                        @foreach([
                            'excellent' => 'Excellent',
                            'very_satisfactory' => 'Very Satisfactory',
                            'needs_improvement' => 'Needs Improvement',
                            'poor' => 'Poor',
                        ] as $key => $label)
                            <div class="settings-threshold-row">
                                <strong>{{ $label }}</strong>
                                <label>
                                    <span>Minimum</span>
                                    <input type="number" step="0.01" min="0" max="10" name="performance_{{ $key }}_min" value="{{ old('performance_'.$key.'_min', $settings['performance_'.$key.'_min']) }}" required>
                                </label>
                                <label>
                                    <span>Maximum</span>
                                    <input type="number" step="0.01" min="0" max="10" name="performance_{{ $key }}_max" value="{{ old('performance_'.$key.'_max', $settings['performance_'.$key.'_max']) }}" required>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($activeSection === 'student')
                <section class="settings-card">
                    <div class="settings-card-heading">
                        <div>
                            <h2>Student Display</h2>
                            <p>Customize student-facing evaluation labels and helper messages.</p>
                        </div>
                    </div>

                    <div class="settings-grid two">
                        <label>
                            <span>Student Evaluation Page Title</span>
                            <input type="text" name="student_evaluation_page_title" value="{{ old('student_evaluation_page_title', $settings['student_evaluation_page_title']) }}" required>
                        </label>
                        <label>
                            <span>Thank You Message</span>
                            <input type="text" name="thank_you_message" value="{{ old('thank_you_message', $settings['thank_you_message']) }}" required>
                        </label>
                        <label class="settings-full">
                            <span>Student Evaluation Instructions</span>
                            <textarea name="student_evaluation_instructions" rows="4">{{ old('student_evaluation_instructions', $settings['student_evaluation_instructions']) }}</textarea>
                        </label>
                    </div>

                    <div class="settings-toggle-list">
                        @foreach([
                            'show_deadline_to_students' => 'Show deadline to students',
                            'show_progress_bar' => 'Show progress bar',
                            'show_required_question_indicator' => 'Show required question indicator',
                            'show_confirmation_before_submit' => 'Show confirmation before submit',
                        ] as $key => $label)
                            <label class="settings-toggle compact">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($activeSection === 'security')
                <section class="settings-card">
                    <div class="settings-card-heading">
                        <div>
                            <h2>Security Settings</h2>
                            <p>Set session, password, and login protection rules used by the portal policy.</p>
                        </div>
                    </div>

                    <div class="settings-grid two">
                        <label>
                            <span>Session Timeout (minutes)</span>
                            <input type="number" name="session_timeout" min="15" max="1440" value="{{ old('session_timeout', $settings['session_timeout']) }}" required>
                        </label>
                        <label>
                            <span>Password Minimum Length</span>
                            <input type="number" name="password_min_length" min="8" max="32" value="{{ old('password_min_length', $settings['password_min_length']) }}" required>
                        </label>
                        <label>
                            <span>Login Attempt Limit</span>
                            <input type="number" name="login_attempt_limit" min="3" max="20" value="{{ old('login_attempt_limit', $settings['login_attempt_limit']) }}" required>
                        </label>
                        <label>
                            <span>Account Lock Duration (minutes)</span>
                            <input type="number" name="account_lock_duration" min="5" max="1440" value="{{ old('account_lock_duration', $settings['account_lock_duration']) }}" required>
                        </label>
                    </div>

                    <div class="settings-toggle-list">
                        @foreach([
                            'strong_password_required' => 'Require strong passwords',
                            'maintenance_mode' => 'Maintenance mode',
                        ] as $key => $label)
                            <label class="settings-toggle compact">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="settings-savebar">
                <div>
                    <strong>Save {{ $currentSection['label'] }} settings</strong>
                    <span>Changes are validated and recorded in the audit trail.</span>
                </div>
                <button type="submit" class="btn-primary">Save Settings</button>
            </div>
        </form>
    @else
        <section class="settings-card">
            <div class="settings-card-heading">
                <div>
                    <h2>Admin Profile</h2>
                    <p>Update your display identity and password.</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.profile') }}" method="POST" enctype="multipart/form-data" class="settings-grid two">
                @csrf
                <label>
                    <span>Display Name</span>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                </label>
                <label>
                    <span>Admin Email</span>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                </label>
                <label>
                    <span>Profile Picture</span>
                    <input type="file" name="profile_picture" accept=".jpg,.jpeg,.png,.webp">
                </label>
                <label>
                    <span>New Password</span>
                    <input type="password" name="password">
                </label>
                <label>
                    <span>Confirm Password</span>
                    <input type="password" name="password_confirmation">
                </label>
                <div class="settings-profile-actions">
                    <button type="submit" class="btn-primary">Save Profile Changes</button>
                </div>
            </form>
        </section>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fallbackText = 'FEU';
            const page = document.getElementById('settings');
            const uploadUrl = page?.dataset.brandingUploadUrl;
            const csrfToken = page?.dataset.brandingCsrf;

            const renderPreview = (fieldName, src) => {
                const preview = document.querySelector(`[data-branding-preview="${fieldName}"]`);

                if (!preview) {
                    return;
                }

                preview.innerHTML = '';

                if (!src) {
                    const fallback = document.createElement('span');
                    fallback.textContent = fallbackText;
                    preview.appendChild(fallback);
                    return;
                }

                const image = document.createElement('img');
                image.src = src;
                image.alt = `${fieldName.replaceAll('_', ' ')} preview`;
                preview.appendChild(image);
            };

            const setStatus = (fieldName, message, state = '') => {
                const status = document.querySelector(`[data-branding-status="${fieldName}"]`);

                if (!status) {
                    return;
                }

                status.textContent = message;
                status.dataset.state = state;
            };

            const renderLogoLockup = (selector, src) => {
                const lockup = document.querySelector(selector);

                if (!lockup || !src) {
                    return;
                }

                let logo = lockup.querySelector('img');
                const badge = lockup.querySelector('.brand-lockup-fallback');

                if (!logo) {
                    logo = document.createElement('img');
                    logo.alt = 'School logo';
                    logo.className = 'brand-lockup-image';
                    lockup.insertBefore(logo, lockup.querySelector('.brand-lockup-text'));
                }

                logo.src = src;
                badge?.remove();
            };

            const renderSavedImages = (images = {}) => {
                Object.entries(images).forEach(([fieldName, src]) => renderPreview(fieldName, src));
                renderLogoLockup('.navbar-brand-lockup', images.header_logo || images.school_logo);
                renderLogoLockup('.sidebar-brand-lockup', images.sidebar_logo || images.school_logo);
            };

            const uploadBrandingImage = async (fieldName, file, input) => {
                if (!uploadUrl || !csrfToken) {
                    setStatus(fieldName, 'Upload route is not available.', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('image_type', fieldName);
                formData.append('image', file);

                setStatus(fieldName, 'Uploading and saving...', 'saving');
                input.disabled = true;

                try {
                    const response = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: formData,
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        const firstError = data.errors ? Object.values(data.errors).flat()[0] : data.message;
                        throw new Error(firstError || 'The image could not be saved.');
                    }

                    renderSavedImages(data.images);
                    input.value = '';
                    document.querySelector(`[data-branding-reset="${fieldName}"]`)?.removeAttribute('checked');
                    setStatus(fieldName, 'Saved. This image will stay after refresh.', 'success');
                } catch (error) {
                    setStatus(fieldName, error.message, 'error');
                } finally {
                    input.disabled = false;
                }
            };

            document.querySelector('[data-branding-picker]')?.addEventListener('change', (event) => {
                document.querySelectorAll('[data-branding-field]').forEach((panel) => {
                    panel.hidden = panel.dataset.brandingField !== event.target.value;
                });
            });

            document.querySelectorAll('[data-branding-field]').forEach((field) => {
                const input = field.querySelector('input[type="file"]');
                const reset = field.querySelector('[data-branding-reset]');
                const fieldName = field.dataset.brandingField;

                field.querySelector(`[data-branding-choose="${fieldName}"]`)?.addEventListener('click', () => input?.click());

                input?.addEventListener('change', () => {
                    const file = input.files?.[0];

                    if (!file) {
                        return;
                    }

                    const src = URL.createObjectURL(file);
                    renderPreview(fieldName, src);
                    setStatus(fieldName, 'Preview ready. Saving now...', 'saving');

                    if (reset) {
                        reset.checked = false;
                    }

                    if (fieldName === 'school_logo') {
                        ['header_logo', 'sidebar_logo', 'login_logo'].forEach((linkedField) => {
                            const linkedReset = document.querySelector(`[data-branding-reset="${linkedField}"]`);

                            if (linkedReset?.checked) {
                                renderPreview(linkedField, src);
                            }
                        });

                        renderLogoLockup('.navbar-brand-lockup', src);
                        renderLogoLockup('.sidebar-brand-lockup', src);
                    }

                    if (fieldName === 'header_logo') {
                        renderLogoLockup('.navbar-brand-lockup', src);
                    }

                    if (fieldName === 'sidebar_logo') {
                        renderLogoLockup('.sidebar-brand-lockup', src);
                    }

                    uploadBrandingImage(fieldName, file, input);
                });

                reset?.addEventListener('change', () => {
                    if (!reset.checked) {
                        return;
                    }

                    input.value = '';
                    const schoolLogo = document.querySelector('[data-branding-preview="school_logo"] img')?.src;
                    renderPreview(fieldName, ['header_logo', 'sidebar_logo', 'login_logo'].includes(fieldName) ? schoolLogo : null);
                    setStatus(fieldName, 'Click Save Settings to apply this reset.', '');
                });
            });

            document.querySelectorAll('[data-pdf-tab]').forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.dataset.pdfTab;

                    document.querySelectorAll('[data-pdf-tab]').forEach((tab) => {
                        const active = tab === button;
                        tab.classList.toggle('active', active);
                        tab.setAttribute('aria-selected', String(active));
                    });

                    document.querySelectorAll('[data-pdf-panel]').forEach((panel) => {
                        panel.hidden = panel.dataset.pdfPanel !== target;
                    });
                });
            });
        });
    </script>
@endpush
