<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        if ($request->user()) {
            $auditLogger->record($request, 'auth.logout', 'Authentication / Security', $request->user(), 'User signed out of the portal.', null, [
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('user.login');
    }
}
