<?php

use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware('role:superadmin')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/audit-logs', SecurityController::class)->name('audit-logs.index');
        Route::get('/security-logs', SecurityController::class)->name('security-logs.index');
        Route::post('/admins', [DashboardController::class, 'store'])->name('addAdmin');
        Route::put('/admins/{admin}', [DashboardController::class, 'update'])->name('updateAdmin');
        Route::delete('/admins/{admin}', [DashboardController::class, 'destroy'])->name('deleteAdmin');
    });
