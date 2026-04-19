<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectUserController;
use App\Http\Controllers\Api\TaskStatusController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskAssignmentController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;



// PUBLIC ROUTES (No authentication required)
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/verify-email', [EmailVerificationController::class, 'verify'])
    ->middleware('throttle.verify');
Route::post('/resend-verification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle.verification');
Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);

// AUTHENTICATED ROUTES (Only requires valid token)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
    Route::get('/email-status', [EmailVerificationController::class, 'checkStatus']);
});

// PROFILES ROUTES (Requires authentication only)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/my-profile', [ProfileController::class, 'myProfile']);
    Route::put('/profiles/{profile}/stats', [ProfileController::class, 'updateStats']);

    Route::get('/profiles/{profile}/skills', [ProfileController::class, 'getSkills']);
    Route::post('/profiles/{profile}/skills', [ProfileController::class, 'addSkill']);
    Route::put('/profiles/{profile}/skills/{skill}', [ProfileController::class, 'updateSkillRating']);
    Route::delete('/profiles/{profile}/skills/{skill}', [ProfileController::class, 'removeSkill']);
    
    Route::apiResource('profiles', ProfileController::class);

    Route::post('/profiles/block/{userId}', [ProfileController::class, 'blockUser']);
    Route::delete('/profiles/unblock/{userId}', [ProfileController::class, 'unblockUser']);
    Route::get('/profiles/blocked-users', [ProfileController::class, 'getBlockedUsers']);

    Route::get('/profiles/{profile}/can-message', [ProfileController::class, 'canSendMessage']);
    Route::get('/profiles/{profile}/can-invite', [ProfileController::class, 'canSendInvitation']);


});

// PROJECTS ROUTES (Requires authentication + active account + verified email)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::get('/my-projects', [ProjectController::class, 'myProjects']);
    Route::get('/my-projects/stats', [ProjectController::class, 'myProjectsStats']);
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);
    Route::apiResource('projects', ProjectController::class);
});

// PROJECT USERS ROUTES (Requires authentication + active account + verified email)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/users')->group(function () {
        Route::get('/', [ProjectUserController::class, 'index']);
        Route::post('/', [ProjectUserController::class, 'addUser']);
        Route::put('/{userId}/role', [ProjectUserController::class, 'updateRole']);
        Route::delete('/{userId}', [ProjectUserController::class, 'removeUser']);
        Route::post('/leave', [ProjectUserController::class, 'leaveProject']);
        Route::post('/transfer-ownership/{userId}', [ProjectUserController::class, 'transferOwnership']);
    });
});

// TASK STATUSES ROUTES (Requires authentication + active account + verified email)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
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

// TASKS ROUTES (Requires authentication + active account + verified email)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::post('/reorder', [TaskController::class, 'reorder']);
        Route::get('/{task}', [TaskController::class, 'show']);
        Route::put('/{task}', [TaskController::class, 'update']);
        Route::put('/{task}/status', [TaskController::class, 'updateStatus']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);
    });
});

// TASK ASSIGNMENTS ROUTES (Requires authentication + active account + verified email)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/tasks/{task}/assignments')->group(function () {
        Route::get('/', [TaskAssignmentController::class, 'index']);
        Route::post('/', [TaskAssignmentController::class, 'assign']);
        Route::delete('/{userId}', [TaskAssignmentController::class, 'unassign']);
    });
    Route::get('/my-assigned-tasks', [TaskAssignmentController::class, 'myAssignedTasks']);
});

// TASK DEPENDENCIES ROUTES (Requires authentication + active account + verified email)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/tasks/{task}/dependencies')->group(function () {
        Route::get('/', [TaskDependencyController::class, 'index']);
        Route::post('/', [TaskDependencyController::class, 'addDependency']);
        Route::delete('/{dependsOnTaskId}', [TaskDependencyController::class, 'removeDependency']);
        Route::put('/{dependsOnTaskId}/type', [TaskDependencyController::class, 'updateDependencyType']);
    });
});

// COMMENTS ROUTES (Requires authentication + active account + verified email)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('tasks/{task}/comments')->group(function () {
        Route::get('/', [CommentController::class, 'index']);
        Route::post('/', [CommentController::class, 'store']);
        Route::get('/{comment}', [CommentController::class, 'show']);
        Route::put('/{comment}', [CommentController::class, 'update']);
        Route::delete('/{comment}', [CommentController::class, 'destroy']);
    });
});

// NOTIFICATIONS ROUTES (Requires authentication + active account + verified email)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::post('/test', [NotificationController::class, 'test']);
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });
});
