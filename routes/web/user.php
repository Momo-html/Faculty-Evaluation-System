<?php

use App\Http\Controllers\User\EvalFormController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')
    ->name('user.')
    ->middleware('role:student')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('user.home'))->name('index');
        Route::get('/home', HomeController::class)->name('home');
        Route::get('/eval-form', EvalFormController::class)->name('eval-form');
        Route::get('/settings', SettingsController::class)->name('settings');
    });

Route::prefix('user')
    ->middleware('role:student')
    ->group(function (): void {
        Route::get('/evaluate/{mapping}', [EvalFormController::class, 'show'])->name('eval.show');
        Route::post('/evaluate', [EvalFormController::class, 'submit'])->name('eval.submit');
    });
