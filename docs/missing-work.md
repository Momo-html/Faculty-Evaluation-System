# Missing Work Checklist

This pass focuses on visible pages and structure. The following parts are still placeholders or need implementation.

## Authentication and RBAC

- Replace the local email/password demo login with Canvas SSO and Microsoft SSO.
- Add password reset backend actions or remove password reset screens if SSO-only login is final.
- Log successful and failed sign-ins into `login_logs`.
- Add policies or permissions if admins need more granular access than the single `admin` role.

## Admin Modules

- Build CSV upload handlers for students, faculty, mappings, and enrollments.
- Store row-level CSV errors into `csv_import_errors`.
- Build create/update/archive flows for faculty records.
- Build form CRUD and enforce only one active `evaluation_forms.is_active = 1`.
- Build mapping CRUD around `subject_mappings` and `student_subjects` without restoring standalone Subject or Section admin modules.
- Generate PDF reports with `barryvdh/laravel-dompdf`.
- Send reports to faculty and log attempts in `report_email_logs`.

## Student Modules

- Load available evaluations from `student_subjects`.
- Save one `evaluation_response` per active form and subject mapping.
- Save scale and text answers into `evaluation_answers`.
- Lock submitted evaluations from duplicate submission.

## Analytics

- Calculate `evaluation_summaries` and `category_summaries`.
- Track `response_daily_counts`.
- Generate `prediction_results` for projected completion dates.
- Exclude student names and identifiers from faculty-facing reports.

## Superadmin Modules

- Build admin account management.
- Build backup creation and restore workflows.
- Add export monitoring through `export_logs`.
- Add system-wide audit views for `activity_logs`, `login_logs`, and `backup_logs`.

## Frontend Polish

- Connect tables and metrics to real database queries.
- Add empty states, loading states, validation states, and success/error messages.
- Add pagination and filters for large tables.
- Add responsive QA for all pages after real data is connected.
