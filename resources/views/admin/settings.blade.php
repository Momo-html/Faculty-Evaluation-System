@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/settings.css') }}">
@endpush

@php
    $imageFields = [
        'school_logo' => ['label' => 'School Logo', 'key' => 'school_logo_path'],
        'header_logo' => ['label' => 'Header Logo', 'key' => 'header_logo_path'],
        'sidebar_logo' => ['label' => 'Sidebar Logo', 'key' => 'sidebar_logo_path'],
        'login_logo' => ['label' => 'Login Page Logo', 'key' => 'login_logo_path'],
        'favicon' => ['label' => 'Favicon', 'key' => 'favicon_path'],
    ];
@endphp

@section('content')
<div id="settings" class="settings-page" data-branding-upload-url="{{ route('admin.settings.branding-image') }}" data-branding-csrf="{{ csrf_token() }}">
    <section class="settings-hero">
        <div>
            <p class="settings-eyebrow">System Configuration</p>
            <h1>Settings Module</h1>
            <p>Manage branding, evaluation controls, reports, student display, security, and your admin profile.</p>
        </div>
    </section>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-danger">
            <strong>Please check the settings form:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="settings-stack">
        @csrf

        <section class="settings-card">
            <div class="settings-card-heading">
                <div>
                    <h2>Branding Settings</h2>
                    <p>These values appear across headers, login screens, reports, and PDF exports.</p>
                </div>
            </div>

            <div class="settings-image-grid">
                @foreach($imageFields as $input => $meta)
                    <div class="settings-image-field" data-branding-field="{{ $input }}">
                        <div class="settings-image-preview" data-branding-preview="{{ $input }}">
                            @if(($portalImage)($meta['key']))
                                <img src="{{ ($portalImage)($meta['key']) }}" alt="{{ $meta['label'] }} preview">
                            @else
                                <span>FEU</span>
                            @endif
                        </div>
                        <label>
                            <span>{{ $meta['label'] }}</span>
                            <input type="file" name="{{ $input }}" accept=".jpg,.jpeg,.png,.webp">
                        </label>
                        <small>JPG, PNG, or WEBP only. Maximum size: 2 MB.</small>
                        @if(in_array($input, ['header_logo', 'sidebar_logo', 'login_logo'], true))
                            <small>School Logo updates this too. Upload here only if it should be different.</small>
                        @endif
                        <small class="settings-upload-status" data-branding-status="{{ $input }}"></small>
                        <label class="settings-check">
                            <input type="checkbox" name="reset_{{ $input }}" value="1">
                            <span>Reset to default</span>
                        </label>
                    </div>
                @endforeach
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
                    <textarea name="school_address" rows="2">{{ old('school_address', $settings['school_address']) }}</textarea>
                </label>
            </div>
        </section>

        <section class="settings-card">
            <div class="settings-card-heading">
                <div>
                    <h2>Evaluation Control Settings</h2>
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
                    <textarea name="default_evaluation_instructions" rows="3">{{ old('default_evaluation_instructions', $settings['default_evaluation_instructions']) }}</textarea>
                </label>
            </div>

            <div class="settings-toggle-grid">
                @foreach([
                    'allow_late_submissions' => 'Allow late submissions',
                    'allow_one_submission_only' => 'Allow one submission only',
                    'allow_student_edit_submissions' => 'Allow students to edit submitted evaluations',
                ] as $key => $label)
                    <label class="settings-toggle">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="settings-card">
            <div class="settings-card-heading">
                <div>
                    <h2>Report and PDF Settings</h2>
                    <p>These options control report visibility and PDF document details.</p>
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
            </div>

            <div class="settings-toggle-grid">
                @foreach([
                    'allow_pdf_export' => 'Allow PDF downloads',
                    'include_school_logo_pdf' => 'Include school logo in PDF',
                    'include_school_name_pdf' => 'Include school name in PDF header',
                    'include_generated_date_pdf' => 'Include generated date',
                    'include_prepared_by_pdf' => 'Include prepared by',
                    'include_signature_line_pdf' => 'Include signature line',
                ] as $key => $label)
                    <label class="settings-toggle">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="settings-card">
            <div class="settings-card-heading">
                <div>
                    <h2>Student Evaluation Display</h2>
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
                    <textarea name="student_evaluation_instructions" rows="3">{{ old('student_evaluation_instructions', $settings['student_evaluation_instructions']) }}</textarea>
                </label>
            </div>

            <div class="settings-toggle-grid">
                @foreach([
                    'show_deadline_to_students' => 'Show deadline to students',
                    'show_progress_bar' => 'Show progress bar',
                    'show_required_question_indicator' => 'Show required question indicator',
                    'show_confirmation_before_submit' => 'Show confirmation before submit',
                ] as $key => $label)
                    <label class="settings-toggle">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </section>

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

            <div class="settings-toggle-grid">
                @foreach([
                    'strong_password_required' => 'Require strong passwords',
                    'maintenance_mode' => 'Maintenance mode',
                ] as $key => $label)
                    <label class="settings-toggle">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key]) === '1')>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="settings-savebar">
            <div>
                <strong>Save system configuration</strong>
                <span>Logo uploads save instantly. Other settings are saved here and recorded in the audit trail.</span>
            </div>
            <button type="submit" class="btn-primary">Save System Settings</button>
        </div>
    </form>

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
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fallbackText = 'FEU';
            const page = document.getElementById('settings');
            const fields = document.querySelectorAll('[data-branding-field]');
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

                if (badge) {
                    badge.remove();
                }
            };

            const renderSavedImages = (images = {}) => {
                Object.entries(images).forEach(([fieldName, src]) => {
                    renderPreview(fieldName, src);
                });

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
                    setStatus(fieldName, 'Saved. This logo will stay after refresh.', 'success');
                } catch (error) {
                    setStatus(fieldName, error.message, 'error');
                } finally {
                    input.disabled = false;
                }
            };

            fields.forEach((field) => {
                const input = field.querySelector('input[type="file"]');
                const reset = field.querySelector('input[type="checkbox"]');
                const fieldName = field.dataset.brandingField;

                if (!input || !fieldName) {
                    return;
                }

                input.addEventListener('change', () => {
                    const file = input.files?.[0];

                    if (!file) {
                        return;
                    }

                    const src = URL.createObjectURL(file);
                    renderPreview(fieldName, src);
                    setStatus(fieldName, 'Preview ready. Saving now...', 'saving');

                    if (fieldName === 'school_logo') {
                        const headerInput = document.querySelector('input[name="header_logo"]');
                        const sidebarInput = document.querySelector('input[name="sidebar_logo"]');
                        const loginInput = document.querySelector('input[name="login_logo"]');

                        if (!headerInput?.files?.length) {
                            renderPreview('header_logo', src);
                        }

                        if (!sidebarInput?.files?.length) {
                            renderPreview('sidebar_logo', src);
                        }

                        if (!loginInput?.files?.length) {
                            renderPreview('login_logo', src);
                        }

                        renderLogoLockup('.navbar-brand-lockup', src);
                        renderLogoLockup('.sidebar-brand-lockup', src);
                    }

                    if (fieldName === 'header_logo') {
                        renderLogoLockup('.navbar-brand-lockup', src);
                    }

                    if (fieldName === 'sidebar_logo') {
                        renderLogoLockup('.sidebar-brand-lockup', src);
                    }

                    if (reset) {
                        reset.checked = false;
                    }

                    uploadBrandingImage(fieldName, file, input);
                });

                reset?.addEventListener('change', () => {
                    if (reset.checked) {
                        input.value = '';
                        renderPreview(fieldName, null);
                        setStatus(fieldName, 'Click Save System Settings to reset this logo.', '');
                    }
                });
            });
        });
    </script>
@endpush
