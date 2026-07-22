# Student and Faculty Management Guide

This guide explains the code in beginner-friendly terms. Run commands from the Laravel folder—the folder containing `artisan`.

## How the data connects

- A student is a row in `users` with `role = student`.
- A faculty profile is stored in `faculty` and links to a login row in `users` through `faculty.user_id`.
- Both profiles point to `departments`.
- A student points to a current `section` through `users.section_id`; every change is recorded in `student_section_allocations`.
- `subject_mappings` connects one faculty member, subject, section, school year, and semester.
- `student_subjects` enrolls students in those mappings.
- `evaluation_responses` points to the student (`user_id`) and mapping (`subject_mapping_id`). This is why mappings with submitted evaluations cannot be removed.

## Module 1: Faculty Management

### Step 1: Inspect the existing files and database

The project already had `faculty`, `departments`, `subjects`, `sections`, and `subject_mappings`. It also had a placeholder `FacultyController` and demo Blade page. We reused them rather than create duplicates.

### Step 2: Plan the needed files

- Migration: `database/migrations/2026_07_22_010000_extend_faculty_and_student_management.php`
- Model: `app/Models/Faculty.php`
- Controller: `app/Http/Controllers/Admin/FacultyController.php`
- Validation: `app/Http/Requests/Admin/FacultyRequest.php` and `FacultyAssignmentRequest.php`
- View: `resources/views/admin/faculty.blade.php`
- Routes: `routes/web/admin.php`
- Audit helper: `app/Services/ActivityLogger.php`

### Step 3: Create or update the database migration

The migration adds `employee_id`, `user_id`, and `deleted_at` to `faculty`. `employee_id` is unique, `user_id` connects the profile to credentials, and `deleted_at` enables safe archiving.

Run `php artisan migrate` after pulling the code. Do not edit an old migration after teammates have already run it; add a new migration instead.

### Step 4: Create or update the model

`Faculty` uses `SoftDeletes` and defines `department`, `user`, and `subjectMappings` relationships. These methods let Laravel load related records without manual SQL joins.

### Step 5: Create or update the controller

`FacultyController` now has `index`, `store`, `update`, `destroy`, `assignSubject`, and `unassignSubject`. Store/update use database transactions so the login and profile either both save or both roll back.

### Step 6: Create or update form request validation

`FacultyRequest` requires a unique employee ID, valid unique email, existing department, and allowed status. The Faculty Directory does not ask administrators to manage faculty passwords; when a linked account must be created, the application generates a secure internal random password.

### Step 7: Create or update routes

Routes under `/admin/faculty` handle listing, create, update, archive, assign, and unassign. All use the existing `admin.*` naming style.

### Step 8: Create or update Blade views/components

The Faculty Directory includes add/import forms, search filters, pagination, edit controls, assignments, and archive confirmation. It extends `layouts.admin`, preserving the FEU Portal shell.

### Step 9: Add authorization and security

The route group uses `role:admin,superadmin`. Form requests repeat the authorization check as defense in depth. CSRF tokens protect every form. Passwords are hashed automatically by the `User` model cast.

### Step 10: Add audit logging (coordination log)

Create, update, archive, assign, and unassign actions write to `activity_logs` with actor, IP address, browser, action, and description.

### Step 11: Connect the module to existing data

Faculty assignments reuse `subject_mappings`. A mapping cannot be removed if students or evaluation responses depend on it. Department compatibility is checked before assignment.

### Step 12: Test the feature manually

1. Sign in as `admin@example.com` / `password`.
2. Open `/admin/faculty`.
3. Create a faculty record with an existing department.
4. Search for the employee ID and edit the name/status.
5. Assign a subject and section from the same department.
6. Try duplicate email/ID values and confirm validation appears.
7. Archive the record and confirm it leaves the active list.
8. Check `activity_logs` in the database.

### Step 13: Explain possible errors

- “Email already used”: use a unique login email, including emails belonging to archived users.
- “Compatible departments”: choose a faculty, section, and departmental subject from the same department. General subjects with no department are allowed.
- “Cannot remove mapping”: remove unused student assignments first; submitted evaluations intentionally block removal.
- “Table/column not found”: run `php artisan migrate`.

### Step 14: Summarize the module

Faculty management is database-backed CRUD with linked credentials, filtering, pagination, safe archiving, academic assignments, authorization, validation, and audit logs.

## Module 2: Student Management and Section Management

### Step 1: Inspect the existing files and database

The schema already treats students as `users` with `role = student` and already contains `student_subjects`. No duplicate `students` table was created.

### Step 2: Plan the needed files

- Existing model: `app/Models/User.php`
- New history model: `app/Models/StudentSectionAllocation.php`
- Existing controller: `app/Http/Controllers/Admin/StudentsController.php`
- Validation: `app/Http/Requests/Admin/StudentRequest.php` and `StudentAssignmentRequest.php`
- View: `resources/views/admin/students.blade.php`
- Migration and routes are shared with this directory module.

### Step 3: Create or update the database migration

The migration adds `section_id` and `deleted_at` to users, plus `student_section_allocations`. The history table records the section, who changed it, start/end time, and reason.

### Step 4: Create or update the model

`User` now uses soft deletes and relates to department, section, subject mappings, and section allocation history. `StudentSectionAllocation` casts dates to Laravel date objects.

### Step 5: Create or update the controller

`StudentsController` lists, creates, edits, archives, assigns, and unassigns. It rejects non-student route records. Section changes close the old active allocation and create a new one.

### Step 6: Create or update form request validation

Validation requires unique student number/email, name, existing department, status, and confirmed password. A selected section must belong to the selected department.

### Step 7: Create or update routes

`/admin/students` routes cover CRUD. Nested `/assignments` routes manage `student_subjects` safely.

### Step 8: Create or update Blade views/components

The Student Directory has manual entry, CSV import, server-side search/filtering, pagination, edit/archive controls, subject mappings, and the three latest section allocations.

### Step 9: Add authorization and security

Only active admin/superadmin accounts pass the route middleware and Form Request authorization. Blade forms have CSRF protection. Route-model binding and role checks prevent changing another user type.

### Step 10: Add audit logging (coordination log)

Student creation, update, archive, assignment, and unassignment are recorded in `activity_logs`. Section-specific history is kept separately in `student_section_allocations`.

### Step 11: Connect the module to existing data

A student must have a section before receiving a subject mapping, and that mapping must match the current section. Existing evaluation submissions prevent removal of their mapping. Changing the section does not silently erase prior enrollments or evaluations; an admin can review and update mappings deliberately.

### Step 12: Test the feature manually

1. Open `/admin/students` as admin.
2. Add a student and select a section belonging to the chosen department.
3. Edit the section and confirm the history text changes.
4. Assign a subject mapping for that same section.
5. Try a mapping from another section and confirm it is rejected.
6. Search/filter by number, name, department, section, or status.
7. Archive a student and confirm the login can no longer authenticate.

### Step 13: Explain possible errors

- “Section does not belong”: choose a section inside the selected department.
- “Assign section first”: edit the student and set a current section.
- “Different section”: use a subject mapping created for the student’s section.
- “Cannot remove after evaluation”: this protects submitted evaluation history.

### Step 14: Summarize the module

Student management uses the existing user identity, provides full CRUD and pagination, tracks controlled section changes, and safely connects enrollment to evaluations.

## Module 3: CSV Import System

### Step 1: Inspect the existing files and database

`csv_import_logs` and `csv_import_errors` already existed, so the implementation reuses them.

### Step 2: Plan the needed files

- Service: `app/Services/CsvUserImportService.php`
- Controller: `app/Http/Controllers/Admin/CsvImportController.php`
- Request: `app/Http/Requests/Admin/CsvImportRequest.php`
- Existing models: `CsvImportLog` and `CsvImportError`
- Upload forms are in both directory Blade views.

### Step 3: Create or update the database migration

No new CSV table was required. The existing log and error tables already hold totals, status, row number, message, and raw row data.

### Step 4: Create or update the model

`CsvImportLog` gained an `errors()` relationship, making rejected rows easy to display after upload.

### Step 5: Create or update the controller

The small invokable controller validates the upload, calls the service, logs the action, and returns counts/errors to the same page.

### Step 6: Create or update form request validation

Only CSV/TXT files up to 5 MB are accepted, and `type` must be `faculty` or `student`.

### Step 7: Create or update routes

Both pages submit to `POST /admin/directory/import` (`admin.directory.import`).

### Step 8: Create or update Blade views/components

Each page states its exact headers and displays up to 20 rejected-row messages.

### Step 9: Add authorization and security

Admin/superadmin checks apply. Laravel validates upload type/size. CSV values go through Validator and Eloquent; they are not executed as code.

### Step 10: Add audit logging (coordination log)

Each import records the actor and successful/failed totals in both import and activity logs.

### Step 11: Connect the module to existing data

Department codes and section names must match existing academic setup records. Imported users use the same tables, roles, hashing, profile links, and section history as manual entry.

### Step 12: Test the feature manually

Faculty example:

```csv
employee_id,name,email,department_code,password
FAC-200,Sample Teacher,teacher200@example.com,CCS,password123
```

Student example:

```csv
student_number,name,email,department_code,section_name,password
2026-0200,Sample Student,student200@example.com,CCS,BSIT 1A,password123
```

Upload a file with two identical IDs and confirm one succeeds and the duplicate appears as a rejected row.

### Step 13: Explain possible errors

- “Missing CSV columns”: copy the headers exactly; order may differ.
- “Department does not exist”: seed/create the academic department first.
- “Section does not exist”: spelling and department must match.
- “Duplicate”: IDs/emails must be unique in both the database and uploaded file.
- File rejected: save as CSV UTF-8 and keep it below 5 MB.

### Step 14: Summarize the module

Imports are row-isolated: one invalid row does not discard valid rows. Every failure is logged with its row number and data.

## Module 4: Academic Integration Setup

### Step 1: Inspect the existing files and database

Academic setup already supplies departments, sections, subjects, and mappings. This work does not replace that module.

### Step 2: Plan the needed files

Integration lives in the two directory controllers, assignment requests, existing models, and directory views. No separate academic controller was added.

### Step 3: Create or update the database migration

No duplicate academic tables were added. Only student section state/history and faculty profile linkage were missing.

### Step 4: Create or update the model

Relationships on `Faculty`, `User`, `Section`, and `SubjectMapping` express the existing foreign-key design.

### Step 5: Create or update the controller

Faculty assignment creates a `subject_mappings` row. Student assignment creates a `student_subjects` pivot row. Unassign endpoints remove only safe, unused links.

### Step 6: Create or update form request validation

IDs must exist, school year uses `YYYY-YYYY`, and semester is required. Controllers then enforce business compatibility.

### Step 7: Create or update routes

Assignments are nested beneath the relevant faculty/student route to make ownership clear.

### Step 8: Create or update Blade views/components

Assignment controls are inside each record’s Edit details, keeping the existing directory-based navigation.

### Step 9: Add authorization and security

Only admin/superadmin may map records. The controller checks route ownership and dependencies, not just submitted IDs.

### Step 10: Add audit logging (coordination log)

Every academic assignment change records who coordinated it.

### Step 11: Connect the module to existing data

The evaluation engine reads student enrollments and mappings. Preserving mapping IDs preserves evaluation submissions and summaries.

### Step 12: Test the feature manually

Create department → section/subject in Academic Setup → faculty → faculty mapping → student in matching section → student assignment. Then open the student evaluation area and verify the mapped subject is eligible when an active form exists.

### Step 13: Explain possible errors

Create academic setup data before directory assignments. If a submitted evaluation exists, keep the mapping and deactivate/archive profiles instead of deleting history.

### Step 14: Summarize the module

The directories coordinate with—rather than duplicate—the Academic Setup and Evaluation modules.

## Final file and command checklist

Created:

- Two migrations in `database/migrations`
- Five Form Requests in `app/Http/Requests/Admin`
- `CsvImportController`, `CsvUserImportService`, `ActivityLogger`, and `StudentSectionAllocation`
- `resources/views/admin/directory-messages.blade.php`
- `tests/Feature/DirectoryManagementTest.php`

Updated:

- Faculty/Student controllers and views
- `User`, `Faculty`, `Department`, `Section`, `SubjectMapping`, and `CsvImportLog` models
- `routes/web/admin.php`
- `tests/Feature/ModuleStructureTest.php` so database-backed pages migrate their test database

Normal setup commands:

```powershell
composer install
php artisan migrate
php artisan db:seed
php artisan route:clear
php artisan config:clear
php artisan cache:clear
npm install
npm run build
php artisan test
php artisan serve
```

You do not need to rerun `composer install`, `npm install`, or `npm run build` for these changes if dependencies/assets are already installed; no new package was added. Never run `migrate:fresh` on a shared or production database because it deletes data.

Automated verification currently passes 18 tests with 84 assertions.
