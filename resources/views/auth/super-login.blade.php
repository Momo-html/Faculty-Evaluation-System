@extends('layouts.auth')
@section('role', 'Super Admin')

@section('content')
    @if($errors->any())
        <div class="alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('superadmin.login.submit') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="email">Administrative Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="superadmin@feu.edu.ph"
                value="{{ old('email') }}" required autofocus>
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <label for="password">Security Password</label>
                <a href="#" style="font-size: 0.8rem; color: var(--feu-green); text-decoration: none; font-weight: 600;">
                    Reset Access?
                </a>
            </div>
            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <span
                style="background: #eef5ea; color: var(--feu-green); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; border: 1px solid #cbdcc0;">
                HIGH-LEVEL ACCESS REQUIRED
            </span>
        </div>

        <button type="submit" class="btn-primary">Initialize Secure Login</button>
    </form>

    <div style="margin-top: 25px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
        <p style="font-size: 0.85rem; color: var(--text-muted);">
            Standard Staff? <a href="{{ route('admin.login') }}"
                style="color: var(--feu-green); font-weight: 700; text-decoration: none;">Admin Login</a>
        </p>
    </div>
@endsection
