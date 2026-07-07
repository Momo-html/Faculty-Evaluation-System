# Faculty Evaluation System

Laravel prototype for the FEU Cavite faculty evaluation workflow.

## Local Setup

```bash
composer install
php artisan migrate:fresh --seed
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Demo Accounts

| Role | Email | Password |
| --- | --- | --- |
| Student | `student@example.com` | `password` |
| Admin | `admin@example.com` | `password` |
| Superadmin | `superadmin@example.com` | `password` |

## Folder Map

- `routes/web/*.php` keeps routes separated by role.
- `app/Http/Controllers/Admin` owns admin pages.
- `app/Http/Controllers/Auth` owns login, logout, and password reset preview pages.
- `app/Http/Controllers/User` owns student pages.
- `app/Http/Controllers/SuperAdmin` owns superadmin pages.
- `resources/views/*` keeps Blade files grouped by role.
- `public/css/shared/polish.css` holds shared UI polish over the FEU portal base styles.
- `public/css/{admin,auth,superadmin,user}` keeps role-specific styles.
- `public/js/admin/*` holds page-specific admin scripts.
- `public/js/shared/core.js` holds shared admin shell behavior.
- `public/js/user/layout.js` holds shared student shell behavior.
- `app/Support/FrontendDemoData.php` provides preview data until real module queries are wired.

## Current Scope

Standalone admin Subject, Section, and Faculty-Course Mapping modules are removed from the active UI, routes, controllers, views, and page scripts. The related database tables remain because enrollment and evaluation records still use course and class data.

## Verification

```bash
php artisan test
```
