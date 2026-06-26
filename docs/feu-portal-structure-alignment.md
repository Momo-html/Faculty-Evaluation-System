# FEU Portal Structure Alignment

The project now follows the familiar `feu-portal` folder shape, with a few intentional improvements.

## Matched Structure

- Admin controllers live in `app/Http/Controllers/Admin/`.
- Auth controllers live in `app/Http/Controllers/Auth/`.
- Student/user controllers live in `app/Http/Controllers/User/`.
- Superadmin controllers live in `app/Http/Controllers/SuperAdmin/`.
- Blade files remain in the same role folders from `feu-portal`:
  - `resources/views/admin/`
  - `resources/views/auth/`
  - `resources/views/layouts/`
  - `resources/views/superadmin/`
  - `resources/views/user/`
- `resources/views/admin/sections.blade.php` and `resources/views/admin/subjects.blade.php` are intentionally removed from the active admin UI.
- Public assets remain in the same broad folders:
  - `public/css/shared/`
  - `public/css/admin/`
  - `public/css/auth/`
  - `public/css/superadmin/`
  - `public/css/user/`
  - `public/js/shared/`
  - `public/js/admin/`
  - `public/js/user/`

## Improvements Kept

- `routes/web.php` imports role-specific route files from `routes/web/` so route ownership is easier to find.
- RBAC is centralized in `app/Http/Middleware/EnsureUserHasRole.php`.
- Each routeable blade has one page controller.
- The FEU frontend style is preserved, but unused copied module files are removed so developers only see active pages and scripts.
- Shared UI polish is centralized in `public/css/shared/polish.css`; page-specific behavior lives in role-based `public/js/` folders.
- The database schema follows the refined DOCX design instead of the older prototype migrations.
