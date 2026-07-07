<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FormsController;
use App\Http\Controllers\Admin\Reports\FacultyPdfController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SentimentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StudentsController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware('role:admin')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/faculty', FacultyController::class)->name('faculty');
        Route::get('/faculty/{faculty}/export', FacultyPdfController::class)->name('faculty.export');
        Route::get('/forms', FormsController::class)->name('forms');
        Route::get('/reports/faculty-pdf', FacultyPdfController::class)->name('reports.faculty-pdf');
        Route::get('/security', SecurityController::class)->name('security');
        Route::get('/sentiment', SentimentController::class)->name('sentiment');
        Route::get('/settings', SettingsController::class)->name('settings');
        Route::post('/settings/profile', fn () => back()->with('success', 'Profile changes saved for preview.'))->name('settings.profile');
        Route::get('/students', StudentsController::class)->name('students');
        Route::get('/users', UsersController::class)->name('users');
    });
