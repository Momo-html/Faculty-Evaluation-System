<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FormsController;
use App\Http\Controllers\Admin\MappingController;
use App\Http\Controllers\Admin\PerformanceFeedController;
use App\Http\Controllers\Admin\Reports\FacultyPdfController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StudentsController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware('role:admin,superadmin')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/faculty', FacultyController::class)->name('faculty');
        Route::get('/faculty/{faculty}/export', [FacultyPdfController::class, 'export'])->name('faculty.export');
        Route::get('/mapping', MappingController::class)->name('mapping');
        Route::get('/reports/faculty-pdf', FacultyPdfController::class)->name('reports.faculty-pdf');
        Route::get('/reports/department/pdf', [FacultyPdfController::class, 'exportDepartmentPdf'])->name('reports.department.pdf');
        Route::get('/reports/faculty/pdf', [FacultyPdfController::class, 'exportFacultyPdf'])->name('reports.faculty.pdf');
        Route::get('/security', SecurityController::class)->name('security');
        Route::get('/sentiment', [PerformanceFeedController::class, 'index'])->name('sentiment');
        Route::get('/performance-feed', [PerformanceFeedController::class, 'index'])->name('performance-feed.index');
        Route::get('/performance-feed/departments/export-pdf', [PerformanceFeedController::class, 'exportSelectedDepartmentPdf'])->name('performance-feed.departments.export-selected-pdf');
        Route::get('/performance-feed/departments/{department}/export-pdf', [PerformanceFeedController::class, 'exportDepartmentPdf'])->name('performance-feed.departments.export-pdf');
        Route::get('/performance-feed/faculty/export-pdf', [PerformanceFeedController::class, 'exportSelectedFacultyPdf'])->name('performance-feed.faculty.export-selected-pdf');
        Route::get('/performance-feed/faculty/{faculty}/export-pdf', [PerformanceFeedController::class, 'exportFacultyPdf'])->name('performance-feed.faculty.export-pdf');
        Route::get('/settings', SettingsController::class)->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/branding-image', [SettingsController::class, 'brandingImage'])->name('settings.branding-image');
        Route::post('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
        Route::get('/students', StudentsController::class)->name('students');
        Route::get('/users', UsersController::class)->name('users');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware('role:admin')
    ->group(function (): void {
        Route::get('/forms', FormsController::class)->name('forms');
        Route::get('/get-form-data/{form}', [FormsController::class, 'show'])->name('forms.show');
        Route::get('/evaluation-forms/{form}/preview-student', [FormsController::class, 'previewStudent'])->name('forms.preview-student');
        Route::post('/save-evaluation-form', [FormsController::class, 'store'])->name('forms.store');
        Route::delete('/delete-evaluation-form/{form}', [FormsController::class, 'destroy'])->name('forms.destroy');
        Route::patch('/evaluation-forms/{form}/toggle-status', [FormsController::class, 'toggleStatus'])->name('forms.toggle-status');
        Route::post('/evaluation-forms/{form}/questions/reorder', [FormsController::class, 'reorderQuestions'])->name('forms.questions.reorder');
    });
