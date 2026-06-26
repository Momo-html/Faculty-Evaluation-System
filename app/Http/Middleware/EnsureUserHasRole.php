<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route($this->loginRouteFor($roles));
        }

        if ($user->status !== 'active' || ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $roles
     */
    private function loginRouteFor(array $roles): string
    {
        return match ($roles[0] ?? 'student') {
            'admin' => 'admin.login',
            'superadmin' => 'superadmin.login',
            default => 'user.login',
        };
    }
}
