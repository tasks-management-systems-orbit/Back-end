<?php

namespace app\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectUser\AddUserRequest;
use App\Http\Requests\ProjectUser\UpdateUserRoleRequest;
use App\Http\Resources\ProjectUserResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Events\UserJoinedProject;

class ProjectUserController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $users = $project->users()
            ->with('profile')
            ->get()
            ->map(function ($user) use ($project) {
                $user->role = $user->pivot->role;
                return $user;
            });

        $owner = null;
        $managers = [];
        $usersList = [];
        $observers = [];

        foreach ($users as $user) {
            $role = $user->pivot->role;

            if ($role === 'owner') {
                $owner = $user;
            } elseif ($role === 'manager') {
                $managers[] = $user;
            } elseif ($role === 'user') {
                $usersList[] = $user;
            } elseif ($role === 'observer') {
                $observers[] = $user;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'owner' => $owner ? new ProjectUserResource($owner) : null,
                'managers' => ProjectUserResource::collection($managers),
                'users' => ProjectUserResource::collection($usersList),
                'observers' => ProjectUserResource::collection($observers),
                'total' => $users->count(),
            ]
        ]);
    }

    public function addUser(AddUserRequest $request, Project $project): JsonResponse
    {
        $userId = $request->user()->id;
        $currentUserRole = $project->getUserRole($userId);

        if ($project->created_by !== $userId && !in_array($currentUserRole, ['owner', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add users to this project'
            ], 403);
        }

        $newUserId = $request->user_id;

        if ($project->hasUser($newUserId)) {
            return response()->json([
                'success' => false,
                'message' => 'User is already a member of this project'
            ], 409);
        }

        try {
            DB::beginTransaction();

            $project->addUser($newUserId, $request->role);

            DB::commit();

            $newUser = User::with('profile')->find($newUserId);
            $newUser->role = $request->role;

            event(new UserJoinedProject($newUser, $project));

            return response()->json([
                'success' => true,
                'message' => 'User added successfully',
                'data' => new ProjectUserResource($newUser)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateRole(UpdateUserRoleRequest $request, Project $project, int $userId): JsonResponse
    {
        $currentUserId = $request->user()->id;
        $currentUserRole = $project->getUserRole($currentUserId);

        if ($project->created_by !== $currentUserId && !in_array($currentUserRole, ['owner', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update user roles'
            ], 403);
        }

        if (!$project->hasUser($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a user of this project'
            ], 404);
        }

        $targetUserRole = $project->getUserRole($userId);

        if ($targetUserRole === 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change the role of the project owner'
            ], 403);
        }

        if ($currentUserRole === 'manager' && $targetUserRole === 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Managers cannot change other managers roles'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $project->updateUserRole($userId, $request->role);

            DB::commit();

            $updatedUser = User::with('profile')->find($userId);
            $updatedUser->role = $request->role;

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
                'data' => new ProjectUserResource($updatedUser)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeUser(Request $request, Project $project, int $userId): JsonResponse
    {
        $currentUserId = $request->user()->id;
        $currentUserRole = $project->getUserRole($currentUserId);

        if ($project->created_by !== $currentUserId && !in_array($currentUserRole, ['owner', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to remove users'
            ], 403);
        }

        if (!$project->hasUser($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a user of this project'
            ], 404);
        }

        $targetUserRole = $project->getUserRole($userId);

        if ($targetUserRole === 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove the project owner'
            ], 403);
        }

        if ($currentUserRole === 'manager' && $targetUserRole === 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Managers cannot remove other managers'
            ], 403);
        }

        if ($currentUserId === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove yourself from the project'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $project->removeUser($userId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User removed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function leaveProject(Request $request, Project $project): JsonResponse
    {
        $userId = $request->user()->id;

        if ($project->created_by === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Project owner cannot leave the project. Transfer ownership first.'
            ], 403);
        }

        if (!$project->hasUser($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a user of this project'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $project->removeUser($userId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'You have left the project successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to leave project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function transferOwnership(Request $request, Project $project, int $userId): JsonResponse
    {
        $currentUserId = $request->user()->id;

        if ($project->created_by !== $currentUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Only the project owner can transfer ownership'
            ], 403);
        }

        if (!$project->hasUser($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a user of this project'
            ], 404);
        }

        if ($currentUserId === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot transfer ownership to yourself'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $project->updateUserRole($currentUserId, 'user');
            $project->updateUserRole($userId, 'owner');
            $project->update(['created_by' => $userId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project ownership transferred successfully',
                'data' => [
                    'new_owner_id' => $userId,
                    'previous_owner_id' => $currentUserId,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer ownership',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
