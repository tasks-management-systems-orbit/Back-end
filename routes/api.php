<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\ProfileController;
use App\Http\Controllers\Api\ProjectController;




// Public routes (no authentication required)
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/verify-email', [EmailVerificationController::class, 'verify']);
Route::post('/resend-verification', [EmailVerificationController::class, 'resend']);

// Protected routes (require authentication token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
    Route::get('/email-status', [EmailVerificationController::class, 'checkStatus']);
});

// Profiles Routes
Route::apiResource('profiles', ProfileController::class);
Route::get('my-profile', [ProfileController::class, 'myProfile'])->middleware('auth:sanctum');
Route::put('profiles/{profile}/stats', [ProfileController::class, 'updateStats']);
Route::get('profiles/search/{keyword}', [ProfileController::class, 'search']);
Route::post('/profiles/{profile}/skills', [ProfileController::class, 'addSkill']);
Route::delete('/profiles/{profile}/skills/{skill}', [ProfileController::class, 'removeSkill']);

// Project Routes
Route::middleware('auth:sanctum')->group(function () {
    // Project Routes
    Route::get('/my-projects', [ProjectController::class, 'myProjects']);
    Route::get('/my-projects/stats', [ProjectController::class, 'myProjectsStats']);
    Route::apiResource('projects', ProjectController::class);
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore']);
});


// ProjectUsres Routes

use App\Http\Controllers\Api\ProjectUserController;

Route::middleware('auth:sanctum')->group(function () {

    // Project User Management Routes
    Route::prefix('projects/{project}/Users')->group(function () {
        Route::get('/', [ProjectUserController::class, 'index']);
        Route::post('/', [ProjectUserController::class, 'addUser']);
        Route::put('/{userId}/role', [ProjectUserController::class, 'updateRole']);
        Route::delete('/{userId}', [ProjectUserController::class, 'removeUser']);
        Route::post('/leave', [ProjectUserController::class, 'leaveProject']);
        Route::post('/transfer-ownership/{userId}', [ProjectUserController::class, 'transferOwnership']);
    });
});
