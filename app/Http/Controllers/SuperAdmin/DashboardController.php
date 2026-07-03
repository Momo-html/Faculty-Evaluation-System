<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreAdminRequest;
use App\Models\Department;
use App\Models\EvaluationResponse;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        if (! Schema::hasTable('users')) {
            return view('superadmin.dashboard', [
                'stats' => ['total_admins' => 0, 'total_students' => 0, 'total_evaluations' => 0],
                'admins' => new LengthAwarePaginator(collect(), 0, 15),
                'departments' => collect(),
            ]);
        }

        return view('superadmin.dashboard', [
            'stats' => [
                'total_admins' => User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])->count(),
                'total_students' => User::query()->where('role', User::ROLE_STUDENT)->count(),
                'total_evaluations' => EvaluationResponse::query()->whereNotNull('submitted_at')->count(),
            ],
            'admins' => User::query()
                ->with('department')
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ->orderBy('role')
                ->orderBy('name')
                ->paginate(15),
            'departments' => Department::query()->orderBy('department_name')->get(),
        ]);
    }

    public function store(StoreAdminRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validated();

        $admin = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'status' => 'active',
        ]);

        $auditLogger->record($request, 'CREATE', 'Superadmin Management', $admin, 'Created '.$validated['role'].' account for '.$admin->email, null, $admin->only(['name', 'email', 'role', 'department_id', 'status']));

        return back()->with('success', 'Administrator account created.');
    }

    public function update(Request $request, User $admin, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless(in_array($admin->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'role' => ['required', 'in:admin,superadmin'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $oldValues = $admin->only(['name', 'email', 'role', 'department_id', 'status']);
        $admin->update($validated);

        $auditLogger->record($request, 'UPDATE', 'Superadmin Management', $admin, 'Updated administrator account '.$admin->email, $oldValues, $admin->only(['name', 'email', 'role', 'department_id', 'status']));

        return back()->with('success', 'Administrator account updated.');
    }

    public function destroy(Request $request, User $admin, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless(in_array($admin->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true), 404);

        if ($request->user()->is($admin)) {
            return back()->withErrors(['admin' => 'You cannot deactivate your own superadmin account while logged in.']);
        }

        $oldValues = $admin->only(['name', 'email', 'role', 'status']);
        $admin->update(['status' => 'inactive']);

        $auditLogger->record($request, 'DELETE', 'Superadmin Management', $admin, 'Deactivated administrator account '.$admin->email, $oldValues, $admin->only(['name', 'email', 'role', 'status']));

        return back()->with('success', 'Administrator account deactivated.');
    }
}
