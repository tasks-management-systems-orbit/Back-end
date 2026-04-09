<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\ProfileController;



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
