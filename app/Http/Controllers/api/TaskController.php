<?php

namespace app\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Requests\Task\ReorderTasksRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Project $project, Request $request): JsonResponse
    {
        $this->checkProjectAccess($project);

        $statusId = $request->get('status_id');
        $assigneeId = $request->get('assignee_id');
        $priority = $request->get('priority');

        $tasks = Task::with(['status', 'creator', 'assignee', 'assignments'])
            ->where('project_id', $project->id)
            ->when($statusId, fn($q) => $q->where('status_id', $statusId))
            ->when($assigneeId, fn($q) => $q->where('assigned_to', $assigneeId))
            ->when($priority, fn($q) => $q->where('priority', $priority))
            ->orderBy('position')
            ->get();

        $grouped = $tasks->groupBy('status_id');

        $statuses = $project->taskStatuses()
            ->withCount('tasks')
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => TaskResource::collection($tasks),
                'grouped_tasks' => $grouped,
                'statuses' => $statuses->map(function ($status) use ($grouped) {
                    return [
                        'id' => $status->id,
                        'name' => $status->name,
                        'position' => $status->position,
                        'tasks_count' => $status->tasks_count,
                        'tasks' => TaskResource::collection($grouped->get($status->id, [])),
                    ];
                }),
            ],
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->checkProjectManager($project);

        $statusId = $request->status_id ?? $project->taskStatuses()->first()?->id;

        if (!$statusId) {
            return response()->json([
                'success' => false,
                'message' => 'Please create task statuses first',
            ], 422);
        }

        $maxPosition = Task::where('project_id', $project->id)
            ->where('status_id', $statusId)
            ->max('position') ?? -1;

        try {
            DB::beginTransaction();

            $task = Task::create([
                'project_id' => $project->id,
                'status_id' => $statusId,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date,
                'created_by' => $request->user()->id,
                'assigned_to' => $request->assigned_to,
                'position' => $maxPosition + 1,
            ]);

            if ($request->has('assignees')) {
                $task->assignments()->sync($request->assignees);
            }

            DB::commit();

            $task->load(['status', 'creator', 'assignee', 'assignments']);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => new TaskResource($task),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Project $project, Task $task): JsonResponse
    {
        $this->checkProjectAccess($project);

        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        $task->load(['status', 'creator', 'assignee', 'assignments', 'dependencies', 'comments']);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task),
        ]);
    }

public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
{
    if ($task->project_id !== $project->id) {
        return response()->json([
            'success' => false,
            'message' => 'Task does not belong to this project',
        ], 404);
    }

    $userId = $request->user()->id;

    $isOwner = $project->isOwner($userId);
    $isTaskCreator = $task->created_by === $userId;

    if (!$isOwner && !$isTaskCreator) {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to update this task. Only the project owner or task creator can edit.',
        ], 403);
    }

    try {
        DB::beginTransaction();

        $task->update($request->only([
            'title',
            'description',
            'priority',
            'due_date',
            'assigned_to',
            'position'
        ]));

        if ($request->has('assignees') && $isOwner) {
            $task->assignments()->sync($request->assignees);
        } elseif ($request->has('assignees') && !$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Only project owner can update task assignments',
            ], 403);
        }

        DB::commit();

        $task->load(['status', 'creator', 'assignee', 'assignments']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => new TaskResource($task),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to update task',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    public function updateStatus(UpdateTaskStatusRequest $request, Project $project, Task $task): JsonResponse
    {
        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        $userId = $request->user()->id;
        $userRole = $project->getUserRole($userId);
        $isOwner = $project->isOwner($userId);
        $isManager = $userRole === 'manager';
        $isUser = $userRole === 'user';
        $isTaskAssignee = ($task->assigned_to === $userId) || $task->assignments()->where('user_id', $userId)->exists();

        if ($isOwner || $isManager) {
        } elseif ($isUser && $isTaskAssignee) {
        } else {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change task status',
            ], 403);
        }

        $oldStatusId = $task->status_id;
        $newStatusId = $request->status_id;

        try {
            DB::beginTransaction();

            Task::where('project_id', $project->id)
                ->where('status_id', $oldStatusId)
                ->where('position', '>', $task->position)
                ->decrement('position');

            $newPosition = $request->position ??

            Task::where('project_id', $project->id)
                    ->where('status_id', $newStatusId)
                    ->max('position') + 1;

            Task::where('project_id', $project->id)
                ->where('status_id', $newStatusId)
                ->where('position', '>=', $newPosition)
                ->increment('position');

            $task->update([
                'status_id' => $newStatusId,
                'position' => $newPosition,
            ]);

            $status = TaskStatus::find($newStatusId);
            if ($status && strtolower($status->name) === 'done') {
                $task->complete();
            }

            DB::commit();

            $task->load(['status', 'creator', 'assignee', 'assignments']);

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully',
                'data' => new TaskResource($task),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reorder(ReorderTasksRequest $request, Project $project): JsonResponse
    {
        $this->checkProjectManager($project);

        try {
            DB::beginTransaction();

            foreach ($request->tasks as $taskData) {
                Task::where('id', $taskData['id'])
                    ->where('project_id', $project->id)
                    ->update([
                        'position' => $taskData['position'],
                        'status_id' => $taskData['status_id'],
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tasks reordered successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder tasks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->checkProjectManager($project);

        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        try {
            DB::beginTransaction();

            Task::where('project_id', $project->id)
                ->where('status_id', $task->status_id)
                ->where('position', '>', $task->position)
                ->decrement('position');

            $task->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            abort(403, 'You do not have permission to manage tasks');
        }
    }
}
