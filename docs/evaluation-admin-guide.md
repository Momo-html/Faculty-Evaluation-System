# Admin Evaluation Module Guide

This guide explains where the evaluation admin, reports, PDF export, audit logs, settings, and superadmin management code lives.

## Step 1: Inspect Existing Files

Checked existing Laravel structure before coding:

- Routes: `routes/web.php`, `routes/web/admin.php`, `routes/web/superadmin.php`, `routes/web/user.php`
- Middleware: `app/Http/Middleware/EnsureUserHasRole.php`
- Existing tables: `evaluation_forms`, `form_questions`, `evaluation_responses`, `evaluation_answers`, `activity_logs`, `pdf_reports`, `export_logs`
- Existing models: `EvaluationForm`, `FormQuestion`, `EvaluationResponse`, `EvaluationAnswer`, `Faculty`, `SubjectMapping`, `User`
- Existing views: `resources/views/admin/*.blade.php`, `resources/views/superadmin/dashboard.blade.php`, `resources/views/user/*.blade.php`

## Step 2: Files Added or Updated

- Migration: `database/migrations/2026_07_03_000001_add_evaluation_admin_module_support.php`
- Models: `EvaluationForm`, `FormQuestion`, `EvaluationResponse`, `EvaluationAnswer`, `ActivityLog`, `Department`, `Faculty`, `SubjectMapping`, `User`, `Setting`
- Controllers: admin dashboard, forms, reports PDF, security, sentiment, settings, users, faculty, superadmin dashboard, student home/evaluation
- Requests: `EvaluationFormRequest`, `SettingsRequest`, `StoreAdminRequest`
- Services: `AuditLogger`, `EvaluationReportService`, `SentimentAnalyzer`
- Views: admin forms, settings, sentiment, users, reports PDF, superadmin dashboard, student evaluation form
- JS: `public/js/admin/forms.js`
- Routes: `routes/web/admin.php`, `routes/web/superadmin.php`

## Step 3: Database Migration

Run:

```bash
php artisan migrate
```

The migration adds:

- `settings` table for evaluation status, semester, academic year, PDF permission, deadline, report visibility, and system name.
- Soft deletes on `evaluation_forms` and `form_questions` so forms/questions with submissions can be archived safely.
- `options` JSON column on `form_questions` for multiple choice answers.
- Audit metadata columns on `activity_logs`: module, record type, record ID, old values, and new values.

## Step 4: Models

Models now include relationships:

- Form has many questions and responses.
- Question belongs to a form and has many answers.
- Response belongs to a form, student user, and subject mapping.
- Subject mapping belongs to faculty, subject, and section.
- Activity log belongs to a user.

These relationships are what let reports group results by faculty, subject, section, form, school year, and semester.

## Step 5: Controllers

Main controller jobs:

- `Admin/FormsController`: create, edit, archive/delete, activate/deactivate forms and questions.
- `Admin/DashboardController`: shows real form, response, faculty, participation, and recent activity statistics.
- `Admin/Reports/FacultyPdfController`: shows report page and exports faculty PDF using DomPDF.
- `Admin/SecurityController`: lists audit logs with search/filter.
- `Admin/SentimentController`: reads written answers and classifies comments as Positive, Neutral, or Negative.
- `Admin/SettingsController`: updates system settings and admin profile.
- `SuperAdmin/DashboardController`: creates, updates, activates/deactivates admin and superadmin accounts.
- `User/EvalFormController`: saves student evaluation responses and answers.

## Step 6: Validation

Form requests validate:

- Evaluation forms and nested questions.
- Settings values.
- Superadmin-created admin accounts.

If validation fails, Laravel redirects back with the error message.

## Step 7: Routes and Security

Admin routes use:

```php
->middleware('role:admin,superadmin')
```

Superadmin routes use:

```php
->middleware('role:superadmin')
```

This means students cannot access admin evaluation pages, admins can manage evaluations/reports/settings, and only superadmins can manage admin accounts.

## Step 8: Blade Views

Views stay inside the existing folders so the project structure remains aligned:

- `resources/views/admin/forms.blade.php`
- `resources/views/admin/settings.blade.php`
- `resources/views/admin/security.blade.php`
- `resources/views/admin/sentiment.blade.php`
- `resources/views/admin/reports/faculty_pdf.blade.php`
- `resources/views/superadmin/dashboard.blade.php`
- `resources/views/user/eval-form.blade.php`

## Step 9: Audit Logging

Important actions are logged through `App\Services\AuditLogger`:

- Create/update/archive forms.
- Update settings.
- Export reports.
- Create/update/deactivate admin accounts.

Logs store user, action, module, record ID, old/new values, IP address, user agent, and timestamp.

## Step 10: Testing Manually

Start the app:

```bash
php artisan serve
```

Open:

- Admin login: `http://127.0.0.1:8000/admin/login`
- Superadmin login: `http://127.0.0.1:8000/superadmin/login`
- Student login: `http://127.0.0.1:8000/user/login`

Demo users from the seeder:

- `admin@example.com` / `password`
- `superadmin@example.com` / `password`
- `student@example.com` / `password`

Manual test flow:

1. Login as admin.
2. Open Form Builder.
3. Create an active form with rating and text questions.
4. Login as student and submit an evaluation.
5. Return as admin and open Dashboard, Sentiment, Reports, and Security Logs.
6. Export a faculty report PDF.
7. Login as superadmin and create/deactivate an admin account.

## Step 11: Common Errors

- `no such table: settings`: run `php artisan migrate`.
- `Route not found`: run `php artisan route:clear`.
- `View not found`: confirm the Blade file exists in `resources/views`.
- `Class not found`: run `composer dump-autoload`.
- `403 Forbidden`: confirm the logged-in user has `admin` or `superadmin` role and `active` status.
- PDF export disabled: enable PDF export in Admin Settings.

## Step 12: Verification Commands

Run:

```bash
php artisan migrate
php artisan test
php artisan route:list --path=admin
```

Current verification result: all tests pass.

## Form Builder Redesign and Route Fix Notes

### What Was Found

- The project already uses separated route files: `routes/web/admin.php`, `routes/web/user.php`, and `routes/web/superadmin.php`.
- The role middleware is `role:admin`, `role:student`, and `role:superadmin` through `EnsureUserHasRole`.
- Existing evaluation tables are `evaluation_forms`, `form_questions`, `evaluation_responses`, and `evaluation_answers`.
- The previous builder used many inline styles and did not look like a dedicated form-builder workspace.
- Form routes previously allowed `admin,superadmin`, which meant Superadmin could reach form manipulation URLs. This was changed.
- Student pages used the active form, but the backend needed stronger checks for active/open/closed status, student mapping access, duplicate submissions, and dynamic question validation.

### Files Modified for This Refinement

- `routes/web/admin.php`: form builder routes are now inside `role:admin` only. Toggle status and reorder routes were added.
- `routes/web/superadmin.php`: monitoring routes for audit/security logs were added. No form manipulation routes were added.
- `resources/views/layouts/admin.blade.php`: supports page-specific CSS and hides the Form Builder sidebar item from Superadmin.
- `resources/views/admin/forms.blade.php`: redesigned into a professional builder page with header, stats, form table, builder workspace, question cards, preview area, and sticky save area.
- `public/css/admin/forms.css`: contains the new Form Builder styling.
- `public/js/admin/forms.js`: supports dynamic question cards, reorder controls, rating scale min/max, multiple choice options, live preview, status toggle, and frontend validation.
- `app/Http/Requests/Admin/EvaluationFormRequest.php`: now allows only Admin users and validates required dates, rating scale limits, and multiple choice options.
- `app/Http/Controllers/Admin/FormsController.php`: now provides stats, saves scale settings, toggles active status, archives/deletes safely, and reorders questions.
- `app/Http/Controllers/User/HomeController.php`: only shows active, open forms and respects student subject mappings when enrollment data exists.
- `app/Http/Controllers/User/EvalFormController.php`: blocks inactive/closed/archived forms, unauthorized mappings, duplicate submissions, invalid ratings, invalid choices, and missing required answers.
- `resources/views/user/eval-form.blade.php`: displays form title, school year, semester, and dynamic rating scale limits.

### How Active Forms Connect to Students

1. Admin creates a form and marks it active.
2. The form must have `is_active = true`.
3. The current time must be between `open_at` and `close_at`.
4. The form must not be soft-deleted.
5. Student dashboard looks for that active/open form.
6. If `student_subjects` has enrollment rows, the student only sees their assigned subject mappings.
7. If no enrollment rows exist yet, the app allows available mappings so the capstone demo can still be tested.
8. When the student submits, the backend repeats the same checks before saving.

### Manual Testing for the Redesign

Admin:

1. Login at `/admin/login`.
2. Open `/admin/forms`.
3. Click `Create New Form`.
4. Enter school year, semester, open date, and close date.
5. Add a rating question and set min/max.
6. Add a multiple choice question with at least two options.
7. Add a text/comment question.
8. Use `Move Up` and `Move Down` to reorder questions.
9. Check the Student Preview panel.
10. Click `Save Form`.
11. Use `Activate` or `Deactivate` in the table.
12. Edit the form again and confirm the questions reload correctly.

Student:

1. Login at `/user/login`.
2. Open `/user/home`.
3. Confirm active evaluations appear only during the open schedule.
4. Open an evaluation.
5. Answer required rating, multiple choice, and text questions.
6. Submit.
7. Return to the dashboard and confirm it shows as completed.
8. Try opening/submitting again and confirm duplicate submission is blocked.

Superadmin:

1. Login at `/superadmin/login`.
2. Open `/superadmin/audit-logs` or `/superadmin/security-logs`.
3. Manually try `/admin/forms`.
4. Confirm access is forbidden because the route requires `role:admin`.

### Common Errors for This Module

- `403 Forbidden` on `/admin/forms`: you are logged in as Superadmin or Student. Use an Admin account.
- Student cannot see a form: check `is_active`, `open_at`, `close_at`, and whether the student is assigned in `student_subjects`.
- Multiple choice will not save: add at least two unique options.
- Rating question will not save: scale maximum must be greater than scale minimum.
- JavaScript changes not appearing: hard refresh the browser or clear cached assets.
- Route not found after editing routes: run `php artisan route:clear`.
