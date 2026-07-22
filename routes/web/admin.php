<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CsvImportController;
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
    ->middleware('role:admin,superadmin')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/faculty', [FacultyController::class, 'index'])->name('faculty');
        Route::post('/faculty', [FacultyController::class, 'store'])->name('faculty.store');
        Route::put('/faculty/{faculty}', [FacultyController::class, 'update'])->name('faculty.update');
        Route::delete('/faculty/{faculty}', [FacultyController::class, 'destroy'])->name('faculty.destroy');
        Route::post('/faculty/{faculty}/assignments', [FacultyController::class, 'assignSubject'])->name('faculty.assignments.store');
        Route::delete('/faculty/{faculty}/assignments/{mapping}', [FacultyController::class, 'unassignSubject'])->name('faculty.assignments.destroy');
        Route::get('/faculty/{faculty}/export', FacultyPdfController::class)->name('faculty.export');
        Route::get('/forms', FormsController::class)->name('forms');
        Route::get('/reports/faculty-pdf', FacultyPdfController::class)->name('reports.faculty-pdf');
        Route::get('/security', SecurityController::class)->name('security');
        Route::get('/sentiment', SentimentController::class)->name('sentiment');
        Route::get('/settings', SettingsController::class)->name('settings');
        Route::post('/settings/profile', fn () => back()->with('success', 'Profile changes saved for preview.'))->name('settings.profile');
        Route::get('/students', [StudentsController::class, 'index'])->name('students');
        Route::post('/students', [StudentsController::class, 'store'])->name('students.store');
        Route::put('/students/{student}', [StudentsController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentsController::class, 'destroy'])->name('students.destroy');
        Route::post('/students/{student}/assignments', [StudentsController::class, 'assignSubject'])->name('students.assignments.store');
        Route::delete('/students/{student}/assignments/{mapping}', [StudentsController::class, 'unassignSubject'])->name('students.assignments.destroy');
        Route::post('/directory/import', CsvImportController::class)->name('directory.import');
        Route::get('/users', UsersController::class)->name('users');
    });
