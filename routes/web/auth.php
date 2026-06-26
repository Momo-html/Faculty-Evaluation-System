<?php

use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\Passwords\EmailController;
use App\Http\Controllers\Auth\Passwords\ResetController;
use App\Http\Controllers\Auth\SuperLoginController;
use App\Http\Controllers\Auth\UserLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/login', fn () => redirect()->route('user.login'))->name('login');
Route::post('/logout', LogoutController::class)->name('logout');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', AdminLoginController::class)->name('login');
    Route::post('/login', [AdminLoginController::class, 'store'])->name('login.submit');
});

Route::prefix('user')->name('user.')->group(function (): void {
    Route::get('/login', UserLoginController::class)->name('login');
    Route::post('/login', [UserLoginController::class, 'store'])->name('login.submit');
    Route::post('/logout', LogoutController::class)->name('logout');
});

Route::prefix('superadmin')->name('superadmin.')->group(function (): void {
    Route::get('/login', SuperLoginController::class)->name('login');
    Route::post('/login', [SuperLoginController::class, 'store'])->name('login.submit');
    Route::post('/logout', LogoutController::class)->name('logout');
});

Route::prefix('password')->name('password.')->group(function (): void {
    Route::get('/reset', EmailController::class)->name('request');
    Route::post('/email', fn (Request $request) => back()
        ->with('status', 'Password reset links are not wired yet in this frontend preview.')
        ->withInput($request->only('email')))->name('email');
    Route::get('/reset/{token}', ResetController::class)->name('reset');
    Route::post('/reset', fn () => redirect()
        ->route('user.login')
        ->with('status', 'Password reset is not wired yet in this frontend preview.'))->name('update');
});
