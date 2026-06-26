<?php

use App\Http\Controllers\SuperAdmin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware('role:superadmin')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/admins', fn () => back()->with('success', 'Admin added for preview.'))->name('addAdmin');
        Route::delete('/admins/{admin}', fn () => back()->with('success', 'Admin removed for preview.'))->name('deleteAdmin');
    });
