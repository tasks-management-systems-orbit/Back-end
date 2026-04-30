<?php

namespace app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectComment\StoreProjectCommentRequest;
use App\Http\Requests\ProjectComment\UpdateProjectCommentRequest;
use App\Http\Resources\ProjectCommentResource;
use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectCommentController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        // Only public projects allow viewing comments
        if ($project->visibility !== 'public') {
            return response()->json([
                'success' => false,
                'message' => 'Comments are only available for public projects'
            ], 403);
        }

        $perPage = $request->input('per_page', 20);

        $comments = $project->projectComments()
            ->with(['user', 'user.profile', 'replies.user', 'replies.user.profile'])
            ->whereNull('parent_id') // Only top-level comments
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProjectCommentResource::collection($comments),
            'meta' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    public function store(StoreProjectCommentRequest $request, Project $project): JsonResponse
    {
        // Only public projects allow comments
        if ($project->visibility !== 'public') {
            return response()->json([
                'success' => false,
                'message' => 'Comments are only allowed on public projects'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $comment = $project->projectComments()->create([
                'user_id' => $request->user()->id,
                'content' => $request->input('content'),
                'parent_id' => $request->input('parent_id'),
            ]);

            DB::commit();

            $comment->load(['user', 'user.profile']);

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => new ProjectCommentResource($comment)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Project $project, ProjectComment $comment): JsonResponse
    {
        if ($comment->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Comment does not belong to this project'
            ], 404);
        }

        if ($project->visibility !== 'public') {
            return response()->json([
                'success' => false,
                'message' => 'This project is not public'
            ], 403);
        }

        $comment->load(['user', 'user.profile', 'replies.user', 'replies.user.profile']);

        return response()->json([
            'success' => true,
            'data' => new ProjectCommentResource($comment)
        ]);
    }

    public function update(UpdateProjectCommentRequest $request, Project $project, ProjectComment $comment): JsonResponse
    {
        if ($comment->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Comment does not belong to this project'
            ], 404);
        }

        $user = $request->user();
        $isOwner = $comment->user_id === $user->id;
        $isProjectOwner = $project->created_by === $user->id;

        if (!$isOwner && !$isProjectOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own comments'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $comment->update(['content' => $request->input('content')]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => new ProjectCommentResource($comment->load(['user', 'user.profile']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Project $project, ProjectComment $comment): JsonResponse
    {
        if ($comment->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Comment does not belong to this project'
            ], 404);
        }

        $user = $request->user();
        $isOwner = $comment->user_id === $user->id;
        $isProjectOwner = $project->created_by === $user->id;

        if (!$isOwner && !$isProjectOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this comment'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $comment->replies()->delete();
            $comment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
