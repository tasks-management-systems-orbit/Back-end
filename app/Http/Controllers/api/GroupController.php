<?php

namespace app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\StoreGroupRequest;
// use App\Http\Requests\Group\TransferManagerRequest;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function index(Project $project, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $groups = Group::with(['manager', 'creator', 'members'])
            ->where('project_id', $project->id)
            ->when(!$project->isOwner($userId), function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('manager_id', $userId)
                        ->orWhereHas('members', function ($sub) use ($userId) {
                            $sub->where('user_id', $userId);
                        });
                });
            })
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => GroupResource::collection($groups),
            'total' => $groups->count()
        ]);
    }

    public function show(Project $project, Group $group, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $canAccess = $project->isOwner($userId) || $group->isMember($userId) || $group->isManager($userId);

        if (!$canAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this group'
            ], 403);
        }

        $group->load(['manager', 'creator', 'members', 'groupTasks' => function ($query) {
            $query->whereNull('parent_task_id');
        }]);

        return response()->json([
            'success' => true,
            'data' => new GroupResource($group)
        ]);
    }

    public function store(StoreGroupRequest $request, Project $project): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            DB::beginTransaction();

            $group = Group::create([
                'project_id' => $project->id,
                'name' => $request->name,
                'description' => $request->description,
                'avatar' => $request->avatar,
                'manager_id' => $request->manager_id,
                'created_by' => $userId,
                'is_active' => true,
            ]);

            $group->addMember($request->manager_id, $userId);

            foreach ($request->member_ids as $memberId) {
                $group->addMember($memberId, $userId);
            }

            DB::commit();

            $group->load(['manager', 'creator', 'members']);

            return response()->json([
                'success' => true,
                'message' => 'Group created successfully',
                'data' => new GroupResource($group)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Project $project, Group $group): JsonResponse
    {
        $userId = $request->user()->id;

        if (!$project->isOwner($userId) && !$group->isManager($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this group'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|min:2',
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|string|max:255|url',
            'is_active' => 'sometimes|boolean',
        ]);

        $group->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully',
            'data' => new GroupResource($group->load(['manager', 'creator', 'members']))
        ]);
    }

    public function destroy(Project $project, Group $group, Request $request): JsonResponse
    {
        if (!$project->isOwner($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Only project owner can delete groups'
            ], 403);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully'
        ]);
    }
}
