<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectUserController;
use App\Http\Controllers\Api\TaskStatusController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskController;


// Public routes
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/verify-email', [EmailVerificationController::class, 'verify']);
Route::post('/resend-verification', [EmailVerificationController::class, 'resend']);

// Auth Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
    Route::get('/email-status', [EmailVerificationController::class, 'checkStatus']);
});

// Profiles Routes (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-profile', [ProfileController::class, 'myProfile']);
    Route::put('/profiles/{profile}/stats', [ProfileController::class, 'updateStats']);
    Route::get('/profiles/search/{keyword}', [ProfileController::class, 'search']);
    Route::post('/profiles/{profile}/skills', [ProfileController::class, 'addSkill']);
    Route::delete('/profiles/{profile}/skills/{skill}', [ProfileController::class, 'removeSkill']);
    Route::apiResource('profiles', ProfileController::class);
});

// Project Routes (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-projects', [ProjectController::class, 'myProjects']);
    Route::get('/my-projects/stats', [ProjectController::class, 'myProjectsStats']);
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);
    Route::apiResource('projects', ProjectController::class);
});

// Project Users Routes (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('projects/{project}/users')->group(function () {
        Route::get('/', [ProjectUserController::class, 'index']);
        Route::post('/', [ProjectUserController::class, 'addUser']);
        Route::put('/{userId}/role', [ProjectUserController::class, 'updateRole']);
        Route::delete('/{userId}', [ProjectUserController::class, 'removeUser']);
        Route::post('/leave', [ProjectUserController::class, 'leaveProject']);
        Route::post('/transfer-ownership/{userId}', [ProjectUserController::class, 'transferOwnership']);
    });
});

// Task Statuses Routes (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('projects/{project}/statuses')->group(function () {
        Route::get('/', [TaskStatusController::class, 'index']);
        Route::post('/', [TaskStatusController::class, 'store']);
        Route::post('/default', [TaskStatusController::class, 'defaultStatuses']);
        Route::post('/reorder', [TaskStatusController::class, 'reorder']);
        Route::get('/{taskStatus}', [TaskStatusController::class, 'show']);
        Route::put('/{taskStatus}', [TaskStatusController::class, 'update']);
        Route::delete('/{taskStatus}', [TaskStatusController::class, 'destroy']);
    });
});

// Comments Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('tasks/{task}/comments')->group(function () {
        Route::get('/', [CommentController::class, 'index']);
        Route::post('/', [CommentController::class, 'store']);
        Route::get('/{comment}', [CommentController::class, 'show']);
        Route::put('/{comment}', [CommentController::class, 'update']);
        Route::delete('/{comment}', [CommentController::class, 'destroy']);
    });
});


// Task Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('projects/{project}/tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::post('/reorder', [TaskController::class, 'reorder']);
        Route::get('/{task}', [TaskController::class, 'show']);
        Route::put('/{task}', [TaskController::class, 'update']);
        Route::put('/{task}/status', [TaskController::class, 'updateStatus']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);
    });
    Route::get('/my-tasks', [TaskController::class, 'myTasks']);
});
