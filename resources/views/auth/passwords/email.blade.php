@extends('layouts.auth')

@php
    $ref = request()->query('ref', 'user');
    $backRoute = ($ref === 'admin') ? route('admin.login') : route('user.login');
    $roleName = ($ref === 'admin') ? 'Admin' : 'Student';
@endphp

@section('role', 'Reset Password')

@section('content')
    @if (session('status'))
        <div class="alert-success" style="background: #e9f0e6; color: #274c07; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; border: 1px solid #274c07;">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Enter your registered email" value="{{ old('email') }}" required autofocus>
        </div>

        <button type="submit" class="btn-primary">Send Reset Link</button>

        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ $backRoute }}" style="font-size: 0.8rem; color: var(--feu-green); text-decoration: none; font-weight: 600;">
                &lt;- Back to Login
            </a>
        </div>
    </form>
@endsection
