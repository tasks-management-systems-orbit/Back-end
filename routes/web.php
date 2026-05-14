<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ============= Admin Routes =============

Route::prefix('admin')->name('admin.')->group(function () {

    // Guest (not logged in as admin)
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login']);
    });

    // Authenticated admin
    Route::middleware(App\Http\Middleware\AdminAuth::class)->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Placeholder routes — will be replaced by real controllers in later phases
        Route::get('/users', fn () => view('admin.dashboard'))->name('users.index');
        Route::get('/projects', fn () => view('admin.dashboard'))->name('projects.index');
        Route::get('/reports', fn () => view('admin.dashboard'))->name('reports.index');
    });
});
