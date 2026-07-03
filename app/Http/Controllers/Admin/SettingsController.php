<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        $defaults = [
            'evaluation_status' => 'closed',
            'current_semester' => '1st Semester',
            'current_academic_year' => now()->year.'-'.now()->addYear()->year,
            'allow_pdf_export' => '1',
            'evaluation_deadline' => null,
            'report_visibility' => 'admins_only',
            'system_name' => 'FEU Cavite Faculty Evaluation Portal',
        ];

        if (! Schema::hasTable('settings')) {
            return view('admin.settings', ['settings' => collect($defaults)]);
        }

        return view('admin.settings', [
            'settings' => collect($defaults)->mapWithKeys(fn ($value, $key) => [$key => Setting::value($key, $value)]),
        ]);
    }

    public function update(SettingsRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = Setting::query()->pluck('value', 'key')->all();
        $settings = $request->validated();
        $settings['allow_pdf_export'] = $request->boolean('allow_pdf_export') ? '1' : '0';

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => is_bool($value) ? 'boolean' : 'string',
                    'group' => 'evaluation',
                    'updated_by' => $request->user()?->id,
                ],
            );
        }

        $auditLogger->record($request, 'UPDATE', 'Settings', null, 'Updated system evaluation settings.', $oldValues, $settings);

        return back()->with('success', 'System settings updated.');
    }

    public function profile(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $oldValues = $user->only(['name', 'email']);
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $auditLogger->record($request, 'UPDATE', 'Settings', $user, 'Updated admin profile.', $oldValues, $user->only(['name', 'email']));

        return back()->with('success', 'Profile updated.');
    }
}
