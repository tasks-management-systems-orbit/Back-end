<?php

use App\Http\Controllers\api\Auth\EmailVerificationController;
use App\Http\Controllers\api\Auth\LoginController;
use App\Http\Controllers\api\Auth\LogoutController;
use App\Http\Controllers\api\Auth\PasswordResetController;
use App\Http\Controllers\api\Auth\RegisterController;
use App\Http\Controllers\api\CommentController;
use App\Http\Controllers\api\FavoriteController;
use App\Http\Controllers\api\FavoriteProjectController;
use App\Http\Controllers\api\NoteController;
use App\Http\Controllers\api\NotificationController;
use App\Http\Controllers\api\ProfileController;
use App\Http\Controllers\api\ProjectController;
use App\Http\Controllers\api\ProjectUserController;
use App\Http\Controllers\api\ReportController;
use App\Http\Controllers\api\SearchController;
use App\Http\Controllers\api\TaskAssignmentController;
use App\Http\Controllers\api\TaskController;
use App\Http\Controllers\api\TaskDependencyController;
use App\Http\Controllers\api\TaskStatusController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GroupMemberController;
use App\Http\Controllers\Api\ProjectCommentController;
use App\Http\Controllers\Api\ProjectReportController;

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

// ============= ROUTES OUTSIDE project.not.locked (Always work) =============

// PROJECT COMMENTS ROUTES (only for public projects)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/comments')->group(function () {
        Route::get('/', [ProjectCommentController::class, 'index']);
        Route::post('/', [ProjectCommentController::class, 'store']);
        Route::get('/{comment}', [ProjectCommentController::class, 'show']);
        Route::put('/{comment}', [ProjectCommentController::class, 'update']);
        Route::delete('/{comment}', [ProjectCommentController::class, 'destroy']);
    });
});

// PROJECT REPORT ROUTES
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('project-reports')->group(function () {
        Route::post('/', [ProjectReportController::class, 'store']);
        Route::get('/', [ProjectReportController::class, 'getAllReports']);
        Route::get('/project/{projectId}', [ProjectReportController::class, 'getProjectReports']);
    });
});

// SEARCH ROUTES
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::get('/search', [SearchController::class, 'search']);
});

// FAVORITE PROJECTS ROUTES
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::post('/my-favorite-projects/toggle/{projectId}', [FavoriteProjectController::class, 'toggle']);
    Route::get('/my-favorite-projects', [FavoriteProjectController::class, 'index']);
    Route::post('/my-favorite-projects', [FavoriteProjectController::class, 'store']);
    Route::delete('/my-favorite-projects/{projectId}', [FavoriteProjectController::class, 'destroy']);
    Route::get('/my-favorite-projects/check/{projectId}', [FavoriteProjectController::class, 'check']);
});

// FAVORITES ROUTES
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::post('/my-favorites/toggle/{userId}', [FavoriteController::class, 'toggle']);
    Route::get('/my-favorites', [FavoriteController::class, 'index']);
    Route::post('/my-favorites', [FavoriteController::class, 'store']);
    Route::delete('/my-favorites/{userId}', [FavoriteController::class, 'destroy']);
    Route::get('/my-favorites/check/{userId}', [FavoriteController::class, 'check']);
});

// NOTES ROUTES
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::get('/my-note', [NoteController::class, 'show']);
    Route::put('/note', [NoteController::class, 'write']);
    Route::delete('/note', [NoteController::class, 'clear']);
});

// PROJECTS ROUTES - Read only (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::get('/my-projects', [ProjectController::class, 'myProjects']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::patch('/projects/{project}/status', [ProjectController::class, 'updateStatus']);
    Route::patch('/projects/{project}/visibility', [ProjectController::class, 'updateVisibility']);

    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);
    // Trash routes
    Route::get('/my-projects/trash', [ProjectController::class, 'trashed']);
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);
    Route::delete('/projects/{project}/force-delete', [ProjectController::class, 'forceDelete']);
    Route::delete('/my-projects/empty-trash', [ProjectController::class, 'emptyTrash']);
});

// PROJECT USERS ROUTES - Read only (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/users')->group(function () {
        Route::get('/', [ProjectUserController::class, 'index']);
        Route::post('/leave', [ProjectUserController::class, 'leaveProject']);
    });
});

// ============= GROUPS ROUTES - Read only =============
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::get('/projects/{project}/groups', [GroupController::class, 'index']);
    Route::get('/projects/{project}/groups/{group}', [GroupController::class, 'show']);
    Route::get('/projects/{project}/groups/{group}/members', [GroupMemberController::class, 'index']);
    Route::post('/projects/{project}/groups/{group}/leave', [GroupMemberController::class, 'leaveGroup']);
});

// ============= GROUPS ROUTES - Write operations =============
Route::middleware(['auth:sanctum', 'is.active', 'verified', 'project.not.locked'])->group(function () {
    Route::post('/projects/{project}/groups', [GroupController::class, 'store']);
    Route::put('/projects/{project}/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/projects/{project}/groups/{group}', [GroupController::class, 'destroy']);
    Route::post('/projects/{project}/groups/{group}/members', [GroupMemberController::class, 'addMember']);
    Route::delete('/projects/{project}/groups/{group}/members/{userId}', [GroupMemberController::class, 'removeMember']);
    Route::post('/projects/{project}/groups/{group}/transfer-manager', [GroupMemberController::class, 'transferManager']);
});

// TASKS ROUTES - Read only (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::get('/{task}', [TaskController::class, 'show']);
    });
});

// TASK ASSIGNMENTS ROUTES - Read only (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/tasks/{task}/assignments')->group(function () {
        Route::get('/', [TaskAssignmentController::class, 'index']);
    });
    Route::get('/my-assigned-tasks', [TaskAssignmentController::class, 'myAssignedTasks']);
});

// TASK DEPENDENCIES ROUTES - Read only (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('projects/{project}/tasks/{task}/dependencies')->group(function () {
        Route::get('/', [TaskDependencyController::class, 'index']);
    });
});


// NOTIFICATIONS ROUTES (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::post('/test', [NotificationController::class, 'test']);
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });
});

// REPORT ROUTES (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('reports')->group(function () {
        Route::post('/', [ReportController::class, 'store']);
        Route::get('/', [ReportController::class, 'getAllReports']);
        Route::get('/user/{userId}', [ReportController::class, 'getUserReports']);
    });
});

// COMMENTS ROUTES (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified'])->group(function () {
    Route::prefix('tasks/{task}/comments')->group(function () {
        Route::get('/', [CommentController::class, 'index']);
        Route::get('/{comment}', [CommentController::class, 'show']);
    });
});


// ============= ROUTES INSIDE project.not.locked (Blocked when project is paused/completed) =============

// COMMENTS ROUTES (always accessible)
Route::middleware(['auth:sanctum', 'is.active', 'verified', 'project.not.locked'])->group(function () {
    Route::prefix('tasks/{task}/comments')->group(function () {
        Route::post('/', [CommentController::class, 'store']);
        Route::put('/{comment}', [CommentController::class, 'update']);
        Route::delete('/{comment}', [CommentController::class, 'destroy']);
    });
});

// PROJECTS ROUTES - Write operations
Route::middleware(['auth:sanctum', 'is.active', 'verified', 'project.not.locked'])->group(function () {
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
});

// PROJECT USERS ROUTES - Write operations
Route::middleware(['auth:sanctum', 'is.active', 'verified', 'project.not.locked'])->group(function () {
    Route::prefix('projects/{project}/users')->group(function () {
        Route::post('/', [ProjectUserController::class, 'addUser']);
        Route::put('/{userId}/role', [ProjectUserController::class, 'updateRole']);
        Route::delete('/{userId}', [ProjectUserController::class, 'removeUser']);
        Route::post('/transfer-ownership/{userId}', [ProjectUserController::class, 'transferOwnership']);
    });
});

// TASK STATUSES ROUTES (all operations - write)
Route::middleware(['auth:sanctum', 'is.active', 'verified', 'project.not.locked'])->group(function () {
    Route::prefix('projects/{project}/statuses')->group(function () {
        Route::post('/', [TaskStatusController::class, 'store']);
        Route::post('/default', [TaskStatusController::class, 'defaultStatuses']);
        Route::post('/reorder', [TaskStatusController::class, 'reorder']);
        Route::put('/{taskStatus}', [TaskStatusController::class, 'update']);
        Route::delete('/{taskStatus}', [TaskStatusController::class, 'destroy']);
    });
});

// TASKS ROUTES - Write operations
Route::middleware(['auth:sanctum', 'is.active', 'verified', 'project.not.locked'])->group(function () {
    Route::prefix('projects/{project}/tasks')->group(function () {
        Route::post('/', [TaskController::class, 'store']);
        Route::post('/reorder', [TaskController::class, 'reorder']);
        Route::put('/{task}', [TaskController::class, 'update']);
        Route::put('/{task}/status', [TaskController::class, 'updateStatus']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);
    });

    // Manager tasks & subtasks (separate from tasks prefix to avoid variable duplication)
    Route::post('/projects/{project}/groups/{group}/manager-tasks', [TaskController::class, 'storeManagerTask']);
    Route::post('/projects/{project}/groups/{group}/tasks/{parentTask}/subtasks', [TaskController::class, 'storeSubTask']);
});

// TASK ASSIGNMENTS ROUTES - Write operations
Route::middleware(['auth:sanctum', 'is.active', 'verified', 'project.not.locked'])->group(function () {
    Route::prefix('projects/{project}/tasks/{task}/assignments')->group(function () {
        Route::post('/', [TaskAssignmentController::class, 'assign']);
        Route::delete('/{userId}', [TaskAssignmentController::class, 'unassign']);
        Route::put('/{assignmentId}/status', [TaskController::class, 'updateTaskAssignmentStatus']);
    });
});

// TASK DEPENDENCIES ROUTES - Write operations
Route::middleware(['auth:sanctum', 'is.active', 'verified', 'project.not.locked'])->group(function () {
    Route::prefix('projects/{project}/tasks/{task}/dependencies')->group(function () {
        Route::post('/', [TaskDependencyController::class, 'addDependency']);
        Route::delete('/{dependsOnTaskId}', [TaskDependencyController::class, 'removeDependency']);
        Route::put('/{dependsOnTaskId}/type', [TaskDependencyController::class, 'updateDependencyType']);
    });
});
