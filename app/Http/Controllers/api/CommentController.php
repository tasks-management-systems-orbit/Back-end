<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function index(Request $request, Task $task): JsonResponse
    {
        $this->checkTaskAccess($request, $task);

        $perPage = $request->get('per_page', 20);

        $comments = $task->comments()
            ->with(['user', 'user.profile'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $this->checkTaskAccess($request, $task);

        try {
            DB::beginTransaction();

            $comment = $task->comments()->create([
                'user_id' => $request->user()->id,
                'content' => $request->validated()['content'],
            ]);

            $comment->load(['user', 'user.profile']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => new CommentResource($comment),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Task $task, int $commentId): JsonResponse
    {
        $this->checkTaskAccess($request, $task);

        $comment = Comment::with(['user', 'user.profile'])
            ->where('id', $commentId)
            ->where('task_id', $task->id)
            ->first();

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or does not belong to this task',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment),
        ]);
    }

    public function update(UpdateCommentRequest $request, Task $task, int $commentId): JsonResponse
    {
        $this->checkTaskAccess($request, $task);

        $comment = Comment::where('id', $commentId)
            ->where('task_id', $task->id)
            ->first();

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or does not belong to this task',
            ], 404);
        }

        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own comments',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $comment->update([
                'content' => $request->validated()['content'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => new CommentResource($comment->load(['user', 'user.profile'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Task $task, int $commentId): JsonResponse
    {
        $this->checkTaskAccess($request, $task);

        $comment = Comment::where('id', $commentId)
            ->where('task_id', $task->id)
            ->first();

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or does not belong to this task',
            ], 404);
        }

        $userId = $request->user()->id;
        $isOwner = $comment->user_id === $userId;
        $isTaskCreator = $task->created_by === $userId;

        $project = $task->project;
        $isProjectManager = $project->isManager($userId);

        if (!$isOwner && !$isTaskCreator && !$isProjectManager) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this comment',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $comment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function checkTaskAccess(Request $request, Task $task): void
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            abort(401, 'Unauthenticated');
        }

        $project = $task->project;

        if (!$project->isOwner($userId) && !$project->hasUser($userId)) {
            abort(403, 'You do not have access to this task');
        }
    }
}
