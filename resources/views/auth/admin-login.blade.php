@extends('layouts.auth')

@section('role', 'Administrator')

@section('content')
    @if ($errors->any())
        <div class="alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.login.submit') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="email">Administrator Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="admin@feu.edu.ph"
                value="{{ old('email') }}" required autofocus>
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <label for="password">Password</label>
                <!-- Inside your Login Blade -->
                <a href="{{ route('password.request') }}?ref=admin"
                    style="font-size: 0.8rem; color: var(--feu-green); text-decoration: none; font-weight: 600;">
                    Forgot Password?
                </a>
            </div>
            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password"
                required>
        </div>

        <button type="submit" class="btn-primary">Access Administrator Dashboard</button>
    </form>

    <div style="margin-top: 25px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
        <p style="font-size: 0.85rem; color: #718096;">
            Are you a student? <a href="{{ route('user.login') }}"
                style="color: var(--feu-green); font-weight: 700; text-decoration: none;">Student Portal</a>
        </p>
    </div>
@endsection