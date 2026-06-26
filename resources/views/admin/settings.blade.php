@extends('layouts.admin')

@section('content')
<div id="settings" class="page-content">
    <h2 style="margin-top:0; color: var(--feu-green); font-weight: 700;">Account Settings</h2>

    <!-- Admin Profile Management Card -->
    <div class="card" style="border-top: 5px solid var(--feu-gold);">
        <form action="{{ route('admin.settings.profile') }}" method="POST">
            @csrf
            <h3 style="margin-top:0; color: var(--feu-green); font-size: 1.2rem;">Admin Profile Management</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 25px;">
                Update your administrative login credentials and system display name.
            </p>

            <div class="form-row two-cols">
                <div class="input-group">
                    <label>Display Name</label>
                    <input type="text" name="name" value="{{ auth()->user()->name }}" placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <label>Admin Email</label>
                    <input type="email" name="email" value="{{ auth()->user()->email }}" placeholder="admin@feucavite.edu.ph" required>
                </div>
            </div>

            <div class="section-divider">
                <h4 style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Security Update</h4>
                <hr style="flex:1; margin-left: 15px; border: 0; border-top: 1px solid #eee;">
            </div>

            <div class="form-row two-cols">
                <div class="input-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="New password">
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" placeholder="Confirm password">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f8f8f8;">
                <button type="submit" class="btn-primary">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Optional: Minimalist Info Box -->
    <div class="card" style="max-width: 900px; background: #fcfdfb; border: 1px dashed #d8e6cf;">
        <div style="display: flex; gap: 15px; align-items: center;">
            <span style="color: var(--feu-green); font-size: 13px; font-weight: 800;">INFO</span>
            <p style="margin: 0; font-size: 0.85rem; color: #555;">
                Changes to your email address will require you to use the new email for your next login. 
                Passwords must be at least 8 characters long.
            </p>
        </div>
    </div>
</div>
@endsection
