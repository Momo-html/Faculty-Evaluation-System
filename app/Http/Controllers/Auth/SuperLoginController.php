<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SuperLoginController extends Controller
{
    public function __invoke(): View
    {
        return view('auth.super-login');
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $auditLogger->record($request, 'auth.login_failed', 'Authentication / Security', null, 'Failed superadmin login attempt for '.$credentials['email'].'.', null, [
                'email' => $credentials['email'],
                'portal' => 'superadmin',
            ]);

            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($request->user()->role !== 'superadmin' || $request->user()->status !== 'active') {
            $auditLogger->record($request, 'auth.permission_denied', 'Authentication / Security', $request->user(), 'Account attempted to access the superadmin portal without the required active superadmin role.', null, [
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'status' => $request->user()->status,
                'portal' => 'superadmin',
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'This account cannot access the superadmin portal.'])->onlyInput('email');
        }

        $auditLogger->record($request, 'auth.login', 'Authentication / Security', $request->user(), 'Successful superadmin sign-in.', null, [
            'email' => $request->user()->email,
            'role' => $request->user()->role,
            'portal' => 'superadmin',
        ]);

        return redirect()->route('superadmin.dashboard');
    }
}
