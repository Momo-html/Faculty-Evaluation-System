<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FormsController;
use App\Http\Controllers\Admin\MappingController;
use App\Http\Controllers\Admin\Reports\FacultyPdfController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SentimentController;
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
        Route::get('/security', SecurityController::class)->name('security');
        Route::get('/sentiment', SentimentController::class)->name('sentiment');
        Route::get('/settings', SettingsController::class)->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
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
        Route::post('/save-evaluation-form', [FormsController::class, 'store'])->name('forms.store');
        Route::delete('/delete-evaluation-form/{form}', [FormsController::class, 'destroy'])->name('forms.destroy');
        Route::patch('/evaluation-forms/{form}/toggle-status', [FormsController::class, 'toggleStatus'])->name('forms.toggle-status');
        Route::post('/evaluation-forms/{form}/questions/reorder', [FormsController::class, 'reorderQuestions'])->name('forms.questions.reorder');
    });
