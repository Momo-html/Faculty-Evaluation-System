@extends('layouts.auth')
@section('role', 'Student')

@section('content')
    @if($errors->any())
        <div class="alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('user.login.submit') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="{{ old('email') }}"
                required autofocus>
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <label for="password">Password</label>
                <a href="{{ route('password.request') }}?ref=user"
                    style="font-size: 0.8rem; color: var(--feu-green); text-decoration: none; font-weight: 600;">
                    Forgot Password?
                </a>
            </div>
            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
        </div>

        <button type="submit" class="btn-primary">Sign In</button>
    </form>
@endsection