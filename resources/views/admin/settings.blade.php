@extends('layouts.admin')

@section('content')
<div id="settings" class="page-content">
    <h2 style="margin-top:0; color: var(--feu-green); font-weight: 700;">System Settings</h2>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="border-top: 5px solid var(--feu-gold);">
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <h3 style="margin-top:0; color: var(--feu-green); font-size: 1.2rem;">Evaluation Controls</h3>

            <div class="form-row two-cols">
                <div class="input-group">
                    <label>Evaluation Status</label>
                    <select name="evaluation_status" required>
                        <option value="open" @selected($settings['evaluation_status'] === 'open')>Open</option>
                        <option value="closed" @selected($settings['evaluation_status'] === 'closed')>Closed</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>PDF Export</label>
                    <label style="display:flex; align-items:center; gap:8px; padding: 8px 0;">
                        <input type="checkbox" name="allow_pdf_export" value="1" @checked($settings['allow_pdf_export'] === '1')>
                        Allow report PDF downloads
                    </label>
                </div>
            </div>

            <div class="form-row two-cols">
                <div class="input-group">
                    <label>Current Academic Year</label>
                    <input type="text" name="current_academic_year" value="{{ $settings['current_academic_year'] }}" required>
                </div>
                <div class="input-group">
                    <label>Current Semester</label>
                    <select name="current_semester" required>
                        <option value="1st Semester" @selected($settings['current_semester'] === '1st Semester')>1st Semester</option>
                        <option value="2nd Semester" @selected($settings['current_semester'] === '2nd Semester')>2nd Semester</option>
                        <option value="Summer" @selected($settings['current_semester'] === 'Summer')>Summer</option>
                    </select>
                </div>
            </div>

            <div class="form-row two-cols">
                <div class="input-group">
                    <label>Evaluation Deadline</label>
                    <input type="date" name="evaluation_deadline" value="{{ $settings['evaluation_deadline'] }}">
                </div>
                <div class="input-group">
                    <label>Report Visibility</label>
                    <select name="report_visibility" required>
                        <option value="admins_only" @selected($settings['report_visibility'] === 'admins_only')>Admins only</option>
                        <option value="faculty_visible" @selected($settings['report_visibility'] === 'faculty_visible')>Faculty visible</option>
                        <option value="closed" @selected($settings['report_visibility'] === 'closed')>Closed</option>
                    </select>
                </div>
            </div>

            <div class="input-group">
                <label>System Name</label>
                <input type="text" name="system_name" value="{{ $settings['system_name'] }}" required>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f8f8f8;">
                <button type="submit" class="btn-primary">Save System Settings</button>
            </div>
        </form>
    </div>

    <div class="card" style="border-top: 5px solid var(--feu-gold);">
        <form action="{{ route('admin.settings.profile') }}" method="POST">
            @csrf
            <h3 style="margin-top:0; color: var(--feu-green); font-size: 1.2rem;">Admin Profile</h3>

            <div class="form-row two-cols">
                <div class="input-group">
                    <label>Display Name</label>
                    <input type="text" name="name" value="{{ auth()->user()->name }}" required>
                </div>
                <div class="input-group">
                    <label>Admin Email</label>
                    <input type="email" name="email" value="{{ auth()->user()->email }}" required>
                </div>
            </div>

            <div class="form-row two-cols">
                <div class="input-group">
                    <label>New Password</label>
                    <input type="password" name="password">
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f8f8f8;">
                <button type="submit" class="btn-primary">Save Profile Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
