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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(?string $section = null): View
    {
        $activeSection = $this->normalizeSection($section);

        return view('admin.settings', [
            'settings' => collect(SettingsSupport::all()),
            'forms' => Schema::hasTable('evaluation_forms') ? EvaluationForm::query()->latest()->get() : collect(),
            'activeSection' => $activeSection,
            'settingSections' => $this->settingSections(),
        ]);
    }

    public function update(SettingsRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = SettingsSupport::all();
        $validated = $request->validated();
        $section = $this->normalizeSection($validated['section'] ?? 'general');
        $settings = collect($validated)
            ->only($this->keysForSection($section))
            ->except(array_merge(array_keys(SettingsSupport::imageKeys()), [
                'reset_school_logo',
                'reset_header_logo',
                'reset_sidebar_logo',
                'reset_login_logo',
                'reset_favicon',
            ]))
            ->all();

        foreach ($this->booleanKeysForSection($section) as $key) {
            $settings[$key] = $request->boolean($key) ? '1' : '0';
        }

        if ($section === 'branding') {
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

        }

        DB::transaction(function () use ($settings, $request, $auditLogger, $oldValues, $section): void {
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

            $auditLogger->record($request, 'settings.updated', 'Settings', null, 'Updated '.$this->settingSections()[$section]['label'].' settings.', $oldValues, SettingsSupport::all());
        });

        return redirect()->route('admin.settings.section', $section)->with('success', $this->settingSections()[$section]['label'].' settings updated.');
    }

    public function brandingImage(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validate([
            'image_type' => ['required', Rule::in(array_keys(SettingsSupport::imageKeys()))],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $oldValues = SettingsSupport::all();
        $input = $validated['image_type'];
        $settingKey = SettingsSupport::imageKeys()[$input];

        if (($oldValues[$settingKey] ?? null) && is_string($oldValues[$settingKey])) {
            $this->deleteImageIfUnused($oldValues[$settingKey], $settingKey, $oldValues);
        }

        $path = $request->file('image')->store('branding', 'public');
        $this->saveSetting($settingKey, $path, $request);

        $auditLogger->record(
            $request,
            'branding.logo_updated',
            'Branding Settings',
            null,
            'Updated '.$this->imageLabel($input).'.',
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
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
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

    private function imageLabel(string $input): string
    {
        return match ($input) {
            'school_logo' => 'the main school logo',
            'header_logo' => 'the header logo',
            'sidebar_logo' => 'the sidebar logo',
            'login_logo' => 'the login page logo',
            'favicon' => 'the favicon',
            default => 'a branding image',
        };
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    private function settingSections(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'description' => 'School identity, portal names, and contact details.',
            ],
            'branding' => [
                'label' => 'Branding',
                'description' => 'Logos, favicon, and visual identity assets.',
            ],
            'evaluation' => [
                'label' => 'Evaluation',
                'description' => 'Active evaluation period and submission rules.',
            ],
            'reports' => [
                'label' => 'PDF Reports',
                'description' => 'Report visibility, titles, and export content.',
            ],
            'performance' => [
                'label' => 'Performance Scale',
                'description' => 'Rating thresholds and reliability settings.',
            ],
            'student' => [
                'label' => 'Student Display',
                'description' => 'Student-facing labels and helper messages.',
            ],
            'security' => [
                'label' => 'Security',
                'description' => 'Password, session, login, and audit preferences.',
            ],
            'profile' => [
                'label' => 'Admin Profile',
                'description' => 'Your name, email, profile photo, and password.',
            ],
        ];
    }

    private function normalizeSection(?string $section): string
    {
        return array_key_exists((string) $section, $this->settingSections()) ? (string) $section : 'general';
    }

    /**
     * @return list<string>
     */
    private function keysForSection(string $section): array
    {
        return match ($section) {
            'general' => ['school_name', 'portal_name', 'system_name', 'school_address', 'school_email', 'school_contact_number', 'footer_text'],
            'branding' => [...array_keys(SettingsSupport::imageKeys()), 'reset_school_logo', 'reset_header_logo', 'reset_sidebar_logo', 'reset_login_logo', 'reset_favicon'],
            'evaluation' => ['evaluation_status', 'current_academic_year', 'current_semester', 'evaluation_start_date', 'evaluation_deadline', 'allow_late_submissions', 'allow_one_submission_only', 'allow_student_edit_submissions', 'default_evaluation_instructions', 'default_evaluation_form_id'],
            'reports' => ['allow_pdf_export', 'report_visibility', 'include_school_logo_pdf', 'include_school_name_pdf', 'include_generated_date_pdf', 'include_prepared_by_pdf', 'include_signature_line_pdf', 'default_report_title', 'department_report_title', 'department_report_intro', 'department_report_footer_text', 'individual_report_title', 'individual_report_intro', 'individual_report_footer_text', 'admin_remarks_label', 'prepared_by_label', 'signature_label', ...SettingsSupport::departmentPdfBooleanKeys(), ...SettingsSupport::individualPdfBooleanKeys()],
            'performance' => ['rating_scale_max', 'performance_excellent_min', 'performance_excellent_max', 'performance_very_satisfactory_min', 'performance_very_satisfactory_max', 'performance_needs_improvement_min', 'performance_needs_improvement_max', 'performance_poor_min', 'performance_poor_max', 'minimum_reliable_responses'],
            'student' => ['student_evaluation_page_title', 'student_evaluation_instructions', 'show_deadline_to_students', 'show_progress_bar', 'show_required_question_indicator', 'show_confirmation_before_submit', 'thank_you_message'],
            'security' => ['session_timeout', 'password_min_length', 'strong_password_required', 'login_attempt_limit', 'account_lock_duration', 'maintenance_mode'],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function booleanKeysForSection(string $section): array
    {
        return array_values(array_intersect($this->keysForSection($section), SettingsSupport::booleanKeys()));
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
