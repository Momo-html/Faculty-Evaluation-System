<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function __invoke(): View
    {
        return view('auth.admin-login');
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        return $this->attemptLogin($request, 'admin', 'admin.dashboard', $auditLogger);
    }

    private function attemptLogin(Request $request, string $role, string $redirectRoute, AuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $auditLogger->record($request, 'auth.login_failed', 'Authentication / Security', null, 'Failed login attempt for '.$credentials['email'].'.', null, [
                'email' => $credentials['email'],
                'portal' => 'admin',
            ]);

            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($request->user()->role !== $role || $request->user()->status !== 'active') {
            $auditLogger->record($request, 'auth.permission_denied', 'Authentication / Security', $request->user(), 'Account attempted to access the admin portal without the required active administrator role.', null, [
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'status' => $request->user()->status,
                'portal' => 'admin',
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'This account cannot access the admin portal.'])->onlyInput('email');
        }

        $auditLogger->record($request, 'auth.login', 'Authentication / Security', $request->user(), 'Successful admin sign-in.', null, [
            'email' => $request->user()->email,
            'role' => $request->user()->role,
            'portal' => 'admin',
        ]);

        return redirect()->route($redirectRoute);
    }
}
