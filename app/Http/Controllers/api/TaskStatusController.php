<?php

namespace app\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStatus\StoreTaskStatusRequest;
use App\Http\Requests\TaskStatus\UpdateTaskStatusRequest;
use App\Http\Requests\TaskStatus\ReorderTaskStatusRequest;
use App\Http\Resources\TaskStatusResource;
use App\Models\Project;
use App\Models\TaskStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TaskStatusController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $this->checkProjectAccess($project);

        $statuses = $project->taskStatuses()
            ->withCount('tasks')
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TaskStatusResource::collection($statuses),
        ]);
    }

    public function store(StoreTaskStatusRequest $request, Project $project): JsonResponse
    {
        $this->checkProjectManager($project);

        $existingStatus = $project->taskStatuses()
            ->where('name', $request->name)
            ->first();

        if ($existingStatus) {
            return response()->json([
                'success' => false,
                'message' => 'A status with this name already exists in this project',
            ], 409);
        }

        try {
            DB::beginTransaction();

            $existingCount = $project->taskStatuses()->count();

            $status = $project->taskStatuses()->create([
                'name' => $request->name,
                'position' => $existingCount + 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task status created successfully',
                'data' => new TaskStatusResource($status),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create task status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Project $project, TaskStatus $taskStatus): JsonResponse
    {
        $this->checkProjectAccess($project);

        if ($taskStatus->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task status does not belong to this project',
            ], 404);
        }

        $taskStatus->loadCount('tasks');

        return response()->json([
            'success' => true,
            'data' => new TaskStatusResource($taskStatus),
        ]);
    }

    public function update(UpdateTaskStatusRequest $request, Project $project, TaskStatus $taskStatus): JsonResponse
    {
        $this->checkProjectManager($project);

        if ($taskStatus->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task status does not belong to this project',
            ], 404);
        }

        $existingStatus = $project->taskStatuses()
            ->where('name', $request->name)
            ->where('id', '!=', $taskStatus->id)
            ->first();

        if ($existingStatus) {
            return response()->json([
                'success' => false,
                'message' => 'A status with this name already exists in this project',
            ], 409);
        }

        try {
            DB::beginTransaction();

            $taskStatus->update([
                'name' => $request->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully',
                'data' => new TaskStatusResource($taskStatus),
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

    public function destroy(Project $project, TaskStatus $taskStatus): JsonResponse
    {
        $this->checkProjectManager($project);

        if ($taskStatus->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task status does not belong to this project',
            ], 404);
        }

        if ($taskStatus->tasks()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete status with existing tasks. Move or delete tasks first.',
            ], 409);
        }

        try {
            DB::beginTransaction();

            $deletedPosition = $taskStatus->position;
            $taskStatus->delete();

            $project->taskStatuses()
                ->where('position', '>', $deletedPosition)
                ->decrement('position');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task status deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reorder(ReorderTaskStatusRequest $request, Project $project): JsonResponse
    {
        $this->checkProjectManager($project);

        try {
            DB::beginTransaction();

            foreach ($request->statuses as $statusData) {
                TaskStatus::where('id', $statusData['id'])
                    ->where('project_id', $project->id)
                    ->update(['position' => $statusData['position']]);
            }

            DB::commit();

            $updatedStatuses = $project->taskStatuses()
                ->orderBy('position')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Task statuses reordered successfully',
                'data' => TaskStatusResource::collection($updatedStatuses),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder task statuses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function defaultStatuses(Project $project): JsonResponse
    {
        $this->checkProjectManager($project);

        $defaultStatuses = ['To Do', 'In Progress', 'Done'];
        $existingCount = $project->taskStatuses()->count();

        if ($existingCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Project already has statuses',
            ], 409);
        }

        try {
            DB::beginTransaction();

            foreach ($defaultStatuses as $index => $statusName) {
                $project->taskStatuses()->create([
                    'name' => $statusName,
                    'position' => $index + 1,
                ]);
            }

            DB::commit();

            $statuses = $project->taskStatuses()->orderBy('position')->get();

            return response()->json([
                'success' => true,
                'message' => 'Default statuses created successfully',
                'data' => TaskStatusResource::collection($statuses),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create default statuses',
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
            abort(403, 'You do not have permission to manage task statuses');
        }
    }
}
