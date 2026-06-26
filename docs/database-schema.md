# Database Schema

Source document:
`C:\Users\aldwi\Downloads\Mobile Devices\refined_faculty_evaluation_system_database_design.docx`

The refined design is implemented in one ordered migration:
`database/migrations/2026_06_23_000000_create_faculty_evaluation_domain_schema.php`

Laravel's default migration still creates the base `users`, `password_reset_tokens`, and `sessions` tables. The domain migration then adds RBAC and SSO fields to `users` and creates the faculty evaluation tables.

## Final Table List

1. `users`
2. `departments`
3. `sections`
4. `faculty`
5. `subjects`
6. `subject_mappings`
7. `student_subjects`
8. `csv_import_logs`
9. `csv_import_errors`
10. `evaluation_forms`
11. `form_questions`
12. `evaluation_responses`
13. `evaluation_answers`
14. `evaluation_summaries`
15. `category_summaries`
16. `response_daily_counts`
17. `prediction_results`
18. `pdf_reports`
19. `report_email_logs`
20. `export_logs`
21. `activity_logs`
22. `login_logs`
23. `backup_logs`

## Important Rules Captured

- `users.role` supports `student`, `admin`, and `superadmin`.
- Faculty records stay separate in `faculty`; faculty members do not log in.
- Only one response is allowed per `evaluation_form_id`, `user_id`, and `subject_mapping_id`.
- Reports and summaries are stored separately from raw student responses so faculty-facing outputs can stay anonymous.
