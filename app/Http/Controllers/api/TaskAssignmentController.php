<?php
// app/Http/Controllers/Api/TaskAssignmentController.php

namespace app\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\AssignUsersRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class TaskAssignmentController extends Controller
{
    public function index(Project $project, Task $task): JsonResponse
    {
        $this->checkProjectAccess($project);

        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        $assignments = $task->assignments()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'primary_assignee' => $task->assignee ? [
                    'id' => $task->assignee->id,
                    'name' => $task->assignee->name,
                    'email' => $task->assignee->email,
                ] : null,
                'additional_assignees' => $assignments->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
                'total' => $assignments->count() + ($task->assigned_to ? 1 : 0),
            ],
        ]);
    }

    public function assign(AssignUsersRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->checkProjectManager($project);

        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        $projectUsers = $project->users()->pluck('users.id')->toArray();
        $projectUsers[] = $project->created_by;

        foreach ($request->user_ids as $userId) {
            if (!in_array($userId, $projectUsers)) {
                return response()->json([
                    'success' => false,
                    'message' => "User ID {$userId} is not a member of this project",
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $task->assignments()->syncWithoutDetaching($request->user_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Users assigned to task successfully',
                'data' => [
                    'assigned_users' => $task->assignments()->get()->map(fn($user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ]),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function unassign(Project $project, Task $task, int $userId): JsonResponse
    {
        $this->checkProjectManager($project);

        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        if ($task->assigned_to === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot unassign the primary assignee. Update the task instead.',
            ], 422);
        }

        if (!$task->assignments()->where('user_id', $userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not assigned to this task',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $task->assignments()->detach($userId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User unassigned from task successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to unassign user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function myAssignedTasks(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $tasks = Task::with(['project', 'status', 'creator', 'assignee', 'assignments'])
            ->where('assigned_to', $userId)
            ->orWhereHas('assignments', fn($q) => $q->where('user_id', $userId))
            ->orderBy('due_date')
            ->orderBy('priority', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => TaskResource::collection($tasks),
                'total' => $tasks->total(),
                'per_page' => $tasks->perPage(),
                'current_page' => $tasks->currentPage(),
            ],
        ]);
    }

    private function checkProjectAccess(Project $project): void
    {
        $userId = request()->user()->id;
        if (!$project->isOwner($userId) && !$project->hasUser($userId)) {
            abort(403, 'You do not have access to this project');
        }
    }

    private function checkProjectManager(Project $project): void
    {
        $userId = request()->user()->id;
        if (!$project->isOwner($userId) && !$project->isManager($userId)) {
            abort(403, 'You do not have permission to manage task assignments');
        }
    }
}
