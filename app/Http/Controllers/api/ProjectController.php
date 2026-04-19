<?php

namespace App\Http\Controllers\Api;

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
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', null);

        $projects = Project::query()
            ->with(['creator', 'users'])
            ->withCount(['users as members_count', 'tasks as tasks_count'])
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
            ->paginate($perPage);

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
        $perPage = $request->get('per_page', 15);
        $role = $request->get('role');

        $query = Project::query()
            ->with(['creator', 'users'])
            ->withCount(['users as members_count', 'tasks as tasks_count'])
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
                    $q->where('user_id', $userId)
                        ->where('role', $role);
                });
            }
        }

        $projects = $query->paginate($perPage);

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
            ]
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

        $project->loadCount(['users as members_count', 'tasks as tasks_count']);

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

        $project->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project->load('creator'))
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

    public function restore(Request $request, int $id): JsonResponse
    {
        $project = Project::withTrashed()->findOrFail($id);

        if ($project->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only project owner can restore the project'
            ], 403);
        }

        $project->restore();

        return response()->json([
            'success' => true,
            'message' => 'Project restored successfully',
            'data' => new ProjectResource($project->load('creator'))
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
}
