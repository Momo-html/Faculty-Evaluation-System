# Module Map

This project is a fresh Laravel scaffold based on the FEU portal file layout.
No old module code was copied into this project.

## Route Folders

Role-specific routes live under `routes/web/` so developers can find page ownership quickly.

| Route file | Purpose |
| --- | --- |
| `routes/web.php` | Root redirect and route-file imports |
| `routes/web/auth.php` | Public login and password reset pages |
| `routes/web/admin.php` | Admin-only pages guarded by `role:admin` |
| `routes/web/superadmin.php` | Superadmin-only pages guarded by `role:superadmin` |
| `routes/web/user.php` | Student/user-only pages guarded by `role:student` |

The RBAC middleware lives at `app/Http/Middleware/EnsureUserHasRole.php` and is registered as `role` in `bootstrap/app.php`.
The shared logout controller lives at `app/Http/Controllers/Auth/LogoutController.php`.

## Page Controllers

Each routeable blade has one invokable controller. Controllers only return their owned blade for now.

| Blade | Controller |
| --- | --- |
| `resources/views/admin/dashboard.blade.php` | `app/Http/Controllers/Admin/DashboardController.php` |
| `resources/views/admin/faculty.blade.php` | `app/Http/Controllers/Admin/FacultyController.php` |
| `resources/views/admin/forms.blade.php` | `app/Http/Controllers/Admin/FormsController.php` |
| `resources/views/admin/mapping.blade.php` | `app/Http/Controllers/Admin/MappingController.php` |
| `resources/views/admin/reports/faculty_pdf.blade.php` | `app/Http/Controllers/Admin/Reports/FacultyPdfController.php` |
| `resources/views/admin/security.blade.php` | `app/Http/Controllers/Admin/SecurityController.php` |
| `resources/views/admin/sentiment.blade.php` | `app/Http/Controllers/Admin/SentimentController.php` |
| `resources/views/admin/settings.blade.php` | `app/Http/Controllers/Admin/SettingsController.php` |
| `resources/views/admin/students.blade.php` | `app/Http/Controllers/Admin/StudentsController.php` |
| `resources/views/admin/users.blade.php` | `app/Http/Controllers/Admin/UsersController.php` |
| `resources/views/auth/admin-login.blade.php` | `app/Http/Controllers/Auth/AdminLoginController.php` |
| `resources/views/auth/super-login.blade.php` | `app/Http/Controllers/Auth/SuperLoginController.php` |
| `resources/views/auth/user-login.blade.php` | `app/Http/Controllers/Auth/UserLoginController.php` |
| `resources/views/auth/passwords/email.blade.php` | `app/Http/Controllers/Auth/Passwords/EmailController.php` |
| `resources/views/auth/passwords/reset.blade.php` | `app/Http/Controllers/Auth/Passwords/ResetController.php` |
| `resources/views/superadmin/dashboard.blade.php` | `app/Http/Controllers/SuperAdmin/DashboardController.php` |
| `resources/views/user/eval-form.blade.php` | `app/Http/Controllers/User/EvalFormController.php` |
| `resources/views/user/home.blade.php` | `app/Http/Controllers/User/HomeController.php` |
| `resources/views/user/settings.blade.php` | `app/Http/Controllers/User/SettingsController.php` |

## Frontend Scope

The visible frontend keeps the FEU portal identity, but the standalone admin Section and Subject modules are intentionally removed from routes, controllers, views, and page scripts. The database tables remain because mapping, enrollment, and evaluation records still depend on section/course data behind the scenes.

## Asset Ownership

Page-specific scripts live under `public/js/admin/` and `public/js/user/`. Shared admin shell helpers stay in `public/js/shared/core.js`; shared admin table/form helpers stay in `public/js/admin/shared.js`. Shared visual polish lives in `public/css/shared/polish.css`, layered after the FEU portal base styles.

| Asset folder | Purpose |
| --- | --- |
| `public/css/shared/` | Cross-role polish and reusable UI rules |
| `public/css/admin/` | Admin shell and admin page base styles |
| `public/css/auth/` | Login and password reset styles |
| `public/css/superadmin/` | Superadmin shell styles |
| `public/css/user/` | Student shell and student page styles |
| `public/js/shared/` | Cross-page shell behavior |
| `public/js/admin/` | Admin page behavior |
| `public/js/user/` | Student shell and page behavior |

## Demo Accounts

Run `php artisan db:seed` after migrations to create local demo accounts.

| Role | Email | Password |
| --- | --- | --- |
| Student | `student@example.com` | `password` |
| Admin | `admin@example.com` | `password` |
| Superadmin | `superadmin@example.com` | `password` |
