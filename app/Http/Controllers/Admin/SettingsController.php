<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\EvaluationForm;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Support\SettingsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.settings', [
            'settings' => collect(SettingsSupport::all()),
            'forms' => Schema::hasTable('evaluation_forms') ? EvaluationForm::query()->latest()->get() : collect(),
        ]);
    }

    public function update(SettingsRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = SettingsSupport::all();
        $validated = $request->validated();
        $settings = collect($validated)
            ->except(array_merge(array_keys(SettingsSupport::imageKeys()), [
                'reset_school_logo',
                'reset_header_logo',
                'reset_sidebar_logo',
                'reset_login_logo',
                'reset_favicon',
            ]))
            ->all();

        foreach (SettingsSupport::booleanKeys() as $key) {
            $settings[$key] = $request->boolean($key) ? '1' : '0';
        }

        foreach (SettingsSupport::imageKeys() as $input => $settingKey) {
            if ($request->boolean('reset_'.$input)) {
                if (($oldValues[$settingKey] ?? null) && is_string($oldValues[$settingKey])) {
                    $this->deleteImageIfUnused($oldValues[$settingKey], $settingKey, $oldValues);
                }

                $settings[$settingKey] = null;
                continue;
            }

            if ($request->hasFile($input)) {
                if (($oldValues[$settingKey] ?? null) && is_string($oldValues[$settingKey])) {
                    $this->deleteImageIfUnused($oldValues[$settingKey], $settingKey, $oldValues);
                }

                $settings[$settingKey] = $request->file($input)->store('branding', 'public');
            }
        }

        $oldSchoolLogo = $oldValues['school_logo_path'] ?? null;

        if (isset($settings['school_logo_path'])) {
            foreach ([
                'header_logo_path' => 'header_logo',
                'sidebar_logo_path' => 'sidebar_logo',
                'login_logo_path' => 'login_logo',
            ] as $settingKey => $input) {
                if (
                    ! $request->hasFile($input)
                    && ! $request->boolean('reset_'.$input)
                ) {
                    $settings[$settingKey] = $settings['school_logo_path'];
                }
            }
        }

        if ($request->boolean('reset_school_logo')) {
            foreach ([
                'header_logo_path' => 'header_logo',
                'sidebar_logo_path' => 'sidebar_logo',
                'login_logo_path' => 'login_logo',
            ] as $settingKey => $input) {
                if (
                    ! $request->hasFile($input)
                    && ! $request->boolean('reset_'.$input)
                    && $oldSchoolLogo
                    && ($oldValues[$settingKey] ?? null) === $oldSchoolLogo
                ) {
                    $settings[$settingKey] = null;
                }
            }
        }

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => in_array($key, SettingsSupport::booleanKeys(), true) ? 'boolean' : 'string',
                    'group' => $this->groupFor($key),
                    'updated_by' => $request->user()?->id,
                ],
            );
        }

        $auditLogger->record($request, 'UPDATE', 'Settings', null, 'Updated system settings.', $oldValues, SettingsSupport::all());

        return back()->with('success', 'System settings updated.');
    }

    public function brandingImage(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validate([
            'image_type' => ['required', Rule::in(array_keys(SettingsSupport::imageKeys()))],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldValues = SettingsSupport::all();
        $input = $validated['image_type'];
        $settingKey = SettingsSupport::imageKeys()[$input];

        if (($oldValues[$settingKey] ?? null) && is_string($oldValues[$settingKey])) {
            $this->deleteImageIfUnused($oldValues[$settingKey], $settingKey, $oldValues);
        }

        $path = $request->file('image')->store('branding', 'public');
        $this->saveSetting($settingKey, $path, $request);

        if ($input === 'school_logo') {
            foreach (['header_logo_path', 'sidebar_logo_path', 'login_logo_path'] as $linkedKey) {
                $this->saveSetting($linkedKey, $path, $request);
            }
        }

        $auditLogger->record(
            $request,
            'UPDATE',
            'Settings',
            null,
            'Updated branding image.',
            collect($oldValues)->only(array_values(SettingsSupport::imageKeys()))->all(),
            collect(SettingsSupport::all())->only(array_values(SettingsSupport::imageKeys()))->all(),
        );

        return response()->json([
            'message' => 'Branding image saved.',
            'images' => $this->brandingImageUrls(),
        ]);
    }

    public function profile(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $oldValues = $user->only(['name', 'email', 'profile_photo_path']);
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $auditLogger->record($request, 'UPDATE', 'Settings', $user, 'Updated admin profile.', $oldValues, $user->only(['name', 'email', 'profile_photo_path']));

        return back()->with('success', 'Profile updated.');
    }

    private function groupFor(string $key): string
    {
        return match (true) {
            str_contains($key, 'logo') || str_contains($key, 'favicon') || str_starts_with($key, 'school_') || in_array($key, ['portal_name', 'system_name', 'footer_text'], true) => 'branding',
            str_contains($key, 'pdf') || str_contains($key, 'report') || str_contains($key, 'signature') || str_contains($key, 'prepared') || str_contains($key, 'remarks') => 'reports',
            str_contains($key, 'performance_') || str_contains($key, 'rating_scale') || str_contains($key, 'reliable_responses') => 'performance',
            str_starts_with($key, 'student_') || str_starts_with($key, 'show_') || $key === 'thank_you_message' => 'student_display',
            str_contains($key, 'password') || str_contains($key, 'session') || str_contains($key, 'login') || str_contains($key, 'lock') || $key === 'maintenance_mode' => 'security',
            default => 'evaluation',
        };
    }

    private function saveSetting(string $key, mixed $value, Request $request): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => in_array($key, SettingsSupport::booleanKeys(), true) ? 'boolean' : 'string',
                'group' => $this->groupFor($key),
                'updated_by' => $request->user()?->id,
            ],
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function brandingImageUrls(): array
    {
        return collect(SettingsSupport::imageKeys())
            ->mapWithKeys(fn (string $settingKey, string $input) => [$input => SettingsSupport::imageUrl($settingKey)])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function deleteImageIfUnused(string $path, string $currentKey, array $oldValues): void
    {
        foreach (SettingsSupport::imageKeys() as $imageKey) {
            if ($imageKey !== $currentKey && ($oldValues[$imageKey] ?? null) === $path) {
                return;
            }
        }

        Storage::disk('public')->delete($path);
    }
}
