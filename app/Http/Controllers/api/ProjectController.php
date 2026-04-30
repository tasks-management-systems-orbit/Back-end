<?php

namespace app\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Events\ProjectCreated;

class ProjectController extends Controller
{
    public function myProjects(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $search = $request->get('search', null);

        $projects = Project::query()
            ->with(['creator', 'users'])
            ->withCount(['users', 'tasks'])
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhereHas('users', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_direction', 'desc'))
            ->get();

        foreach ($projects as $project) {
            $project->user_role = $project->users->firstWhere('id', $userId)?->pivot->role ?? 'none';
            $project->is_owner = $project->created_by === $userId;
        }

        return response()->json([
            'success' => true,
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'total' => $projects->total(),
                'per_page' => $projects->perPage(),
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_direction' => $request->get('sort_direction', 'desc'),
            ]
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $role = $request->get('role');
        $status = $request->get('status');
        $visibility = $request->get('visibility');

        $query = Project::query()
            ->with(['creator', 'users'])
            ->withCount(['users as users_count', 'tasks as tasks_count'])
            ->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)
                    ->orWhereHas('users', function ($sub) use ($userId) {
                        $sub->where('user_id', $userId);
                    });
            });

        if ($role) {
            if ($role === 'owner') {
                $query->where('created_by', $userId);
            } else {
                $query->whereHas('users', function ($q) use ($userId, $role) {
                    $q->where('user_id', $userId)->where('role', $role);
                });
            }
        }

        if ($status && in_array($status, ['active', 'paused', 'completed'])) {
            $query->where('status', $status);
        }

        if ($visibility && in_array($visibility, ['private', 'public'])) {
            $query->where('visibility', $visibility);
        }

        $projects = $query->get();

        foreach ($projects as $project) {
            $project->user_role = $project->users->firstWhere('id', $userId)?->pivot->role ?? 'none';
            $project->is_owner = $project->created_by === $userId;
        }

        return response()->json([
            'success' => true,
            'data' => ProjectResource::collection($projects),
            'total' => $projects->count()
        ]);
    }
    public function show(Request $request, Project $project): JsonResponse
    {
        $userId = $request->user()->id;

        $hasAccess = $project->created_by === $userId ||
            $project->users()->where('user_id', $userId)->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this project'
            ], 403);
        }

        $project->load([
            'creator',
            'users.profile',
            'taskStatuses' => function ($query) {
                $query->orderBy('position');
            },
        ]);

        $project->loadCount(['users as users_count', 'tasks as tasks_count']);

        $project->user_role = $project->users->firstWhere('id', $userId)?->pivot->role ?? 'none';
        $project->is_owner = $project->created_by === $userId;

        return response()->json([
            'success' => true,
            'data' => new ProjectResource($project)
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $project = Project::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $request->image,
                'status' => $request->status ?? 'active',
                'visibility' => $request->visibility ?? 'private',
                'start_date' => $request->start_date ?? now(),
                'end_date' => $request->end_date,
                'created_by' => $request->user()->id,
            ]);

            $project->users()->attach($request->user()->id, ['role' => 'owner']);
            event(new ProjectCreated($project));
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => new ProjectResource($project->load('creator'))
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $userId = $request->user()->id;
        $role = $this->getUserRoleInProject($project, $userId);

        if ($project->created_by !== $userId && !in_array($role, ['owner', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this project'
            ], 403);
        }

        $oldStatus = $project->status;
        $newStatus = $request->status ?? $oldStatus;

        $data = $request->only([
            'name',
            'description',
            'image',
            'status',
            'visibility',
            'start_date',
            'end_date'
        ]);

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $data['end_date'] = now();
        }

        if ($oldStatus === 'completed' && $newStatus !== 'completed') {
            $data['end_date'] = null;
        }

        $project->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project->load('creator'))
        ]);
    }

    public function trashed(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $projects = Project::onlyTrashed()
            ->where('created_by', $userId)
            ->with(['creator'])
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProjectResource::collection($projects),
            'total' => $projects->count(),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        if ($project->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only project owner can delete the project'
            ], 403);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully'
        ]);
    }

    public function restore(Request $request, int $projectId): JsonResponse
    {
        $project = Project::onlyTrashed()->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found or not deleted',
            ], 404);
        }

        if ($project->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the project owner can restore this project',
            ], 403);
        }

        $project->restore();

        return response()->json([
            'success' => true,
            'message' => 'Project restored successfully',
            'data' => new ProjectResource($project->load('creator')),
        ]);
    }

    public function forceDelete(Request $request, int $projectId): JsonResponse
    {
        $project = Project::onlyTrashed()->find($projectId);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        if ($project->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project->load([
            'tasks.taskAssignments',
            'tasks.comments',
            'groups.groupTasks',
            'projectComments',
            'projectReports'
        ]);


        foreach ($project->tasks as $task) {
            $task->taskAssignments()->forceDelete();
            $task->comments()->forceDelete();
            $task->dependencies()->detach();
            $task->forceDelete();
        }

        foreach ($project->groups as $group) {
            $group->members()->detach();
            foreach ($group->groupTasks as $task) {
                $task->taskAssignments()->forceDelete();
                $task->forceDelete();
            }
            $group->forceDelete();
        }

        $project->users()->detach();

        $project->favoritedBy()->detach();

        $project->joinRequests()->forceDelete();

        $project->projectComments()->forceDelete();

        $project->projectReports()->forceDelete();

        $project->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Project and all related data permanently deleted'
        ]);
    }

    public function emptyTrash(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $deletedCount = Project::onlyTrashed()
            ->where('created_by', $userId)
            ->forceDelete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} project(s) permanently deleted",
            'deleted_count' => $deletedCount,
        ]);
    }


    private function getUserRoleInProject(Project $project, int $userId): string
    {
        if ($project->created_by === $userId) {
            return 'owner';
        }

        $member = $project->users->firstWhere('id', $userId);
        return $member?->pivot->role ?? 'none';
    }

    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,paused,completed'
        ]);

        $userId = $request->user()->id;
        $role = $this->getUserRoleInProject($project, $userId);

        if ($project->created_by !== $userId && !in_array($role, ['owner', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change project status'
            ], 403);
        }

        $oldStatus = $project->status;
        $newStatus = $request->status;

        $data = ['status' => $newStatus];

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $data['end_date'] = now();
        }

        if ($oldStatus === 'completed' && $newStatus !== 'completed') {
            $data['end_date'] = null;
        }

        $project->update($data);

        return response()->json([
            'success' => true,
            'message' => "Project status updated to {$newStatus}",
            'data' => [
                'status' => $project->status,
                'status_label' => $project->status_label,
                'end_date' => $project->end_date?->toISOString()
            ]
        ]);
    }
    public function updateVisibility(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'visibility' => 'required|in:private,public'
        ]);

        $userId = $request->user()->id;
        $role = $this->getUserRoleInProject($project, $userId);

        if ($project->created_by !== $userId && !in_array($role, ['owner', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change project visibility'
            ], 403);
        }

        $project->update(['visibility' => $request->visibility]);

        return response()->json([
            'success' => true,
            'message' => "Project visibility updated to {$request->visibility}",
            'data' => [
                'visibility' => $project->visibility,
                'visibility_label' => $project->visibility_label
            ]
        ]);
    }
}
