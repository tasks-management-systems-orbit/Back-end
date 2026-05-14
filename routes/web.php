<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProjectController;
use App\Http\Controllers\Admin\AdminUserController;
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

        // User management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminUserController::class, 'show'])->name('show');
            Route::patch('/{id}/toggle', [AdminUserController::class, 'toggleStatus'])->name('toggle');
            Route::delete('/{id}', [AdminUserController::class, 'destroy'])->name('destroy');
        });

        // Project management
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [AdminProjectController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminProjectController::class, 'show'])->name('show');
            Route::delete('/{id}', [AdminProjectController::class, 'destroy'])->name('destroy');
        });

        // Placeholder route — will be replaced by real controller in later phase
        Route::get('/reports', fn () => view('admin.dashboard'))->name('reports.index');
    });
});
