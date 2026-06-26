@extends('layouts.auth')
@section('role', 'New Password')

@section('content')
    @if($errors->any())
        <div class="alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" value="{{ $email ?? old('email') }}" readonly>
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 8 characters" required autofocus>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Repeat new password" required>
        </div>

        <button type="submit" class="btn-primary">Update Password & Login</button>
    </form>
@endsection