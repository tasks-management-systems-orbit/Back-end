<?php

namespace app\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\ProcessInvitationRequest;
use App\Http\Requests\Invitation\SendInvitationRequest;
use App\Http\Requests\JoinRequest\ProcessJoinRequest;
use App\Http\Requests\JoinRequest\SendJoinRequest;
use App\Models\Project;
use App\Models\Request as JoinRequestModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    // =============  (Join Requests) =============

    /**
     * List all join requests for a project (owner or manager only).
     */
    public function listJoinRequests(Request $request, Project $project): JsonResponse
    {
        try {
            // Authorization: only owner or manager can view join requests
            $userId = $request->user()->id;
            if (!$project->isOwner($userId) && !$project->isManager($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only project owner or manager can view join requests.'
                ], 403);
            }

            $perPage = $request->input('per_page', 20);
            $requests = $project->joinRequests()
                ->with(['sender.profile'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $requests->items(),
                'meta' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('List join requests failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load join requests. Please try again later.'
            ], 500);
        }
    }

    public function sendJoinRequest(SendJoinRequest $request, Project $project): JsonResponse
    {
        try {
            // Check if user already has a pending request
            $existing = Request::where('project_id', $project->id)
                ->where('sender_id', $request->user()->id)
                ->where('type', 'join_request')
                ->where('status', 'pending')
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending request for this project.'
                ], 409);
            }

            DB::beginTransaction();

            $joinRequest = JoinRequestModel::create([
                'sender_id' => $request->user()->id,
                'receiver_id' => $project->created_by,
                'project_id' => $project->id,
                'type' => 'join_request',
                'status' => 'pending',
                'message' => $request->input('message'),
            ]);
            DB::commit();


            return response()->json([
                'success' => true,
                'message' => 'Join request sent successfully.',
                'data' => $joinRequest
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Send join request failed: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'project_id' => $project->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send join request. Please try again later.'
            ], 500);
        }
    }

    public function approveJoinRequest(ProcessJoinRequest $request, Project $project, Request $joinRequest): JsonResponse
    {
        if ($joinRequest->project_id !== $project->id || $joinRequest->type !== 'join_request') {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 404);
        }

        if ($joinRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request already processed.'], 400);
        }

        if ($project->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Project is not active, cannot accept new members.'], 422);
        }

        DB::beginTransaction();
        try {
            $role = $request->input('role', 'user');
            $project->addUser($joinRequest->sender_id, $role);

            $joinRequest->update([
                'status' => 'approved',
                'responded_at' => now(),
                'responded_by' => $request->user()->id,
            ]);

            DB::commit();


            return response()->json(['success' => true, 'message' => 'Join request approved. User added to project.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve join request failed: ' . $e->getMessage(), [
                'request_id' => $joinRequest->id,
                'project_id' => $project->id,
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to approve request. Please try again later.'], 500);
        }
    }

    public function rejectJoinRequest(Request $request, Project $project, Request $joinRequest): JsonResponse
    {
        if ($joinRequest->project_id !== $project->id || $joinRequest->type !== 'join_request') {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 404);
        }

        if ($joinRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request already processed.'], 400);
        }

        DB::beginTransaction();
        try {
            $joinRequest->update([
                'status' => 'rejected',
                'responded_at' => now(),
                'responded_by' => $request->user()->id,
            ]);

            DB::commit();


            return response()->json(['success' => true, 'message' => 'Join request rejected.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reject join request failed: ' . $e->getMessage(), [
                'request_id' => $joinRequest->id,
                'project_id' => $project->id,
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to reject request. Please try again later.'], 500);
        }
    }


    // =============  (Invitations) =============

    public function myInvitations(Request $request): JsonResponse
    {
        try {
            $invitations = JoinRequestModel::where('receiver_id', $request->user()->id)
                ->where('type', 'invitation')
                ->where('status', 'pending')
                ->with(['project', 'sender.profile'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $invitations,
                'total' => $invitations->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching my invitations failed: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load invitations. Please try again later.'
            ], 500);
        }
    }
    public function sendInvitation(SendInvitationRequest $request, Project $project): JsonResponse
    {
        try {
            DB::beginTransaction();

            $inviteeId = $request->invitee_id;

            // Check for pending invitation
            $existing = JoinRequestModel::where('project_id', $project->id)
                ->where('receiver_id', $inviteeId)
                ->where('type', 'invitation')
                ->where('status', 'pending')
                ->exists();

            if ($existing) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Invitation already sent to this user.'
                ], 409);
            }

            $invitation = JoinRequestModel::create([
                'sender_id' => $request->user()->id,
                'receiver_id' => $inviteeId,
                'project_id' => $project->id,
                'type' => 'invitation',
                'status' => 'pending',
                'message' => $request->input('message'),
                'role' => $request->input('role', 'user'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent.',
                'data' => $invitation
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Send invitation failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'sender_id' => $request->user()->id,
                'invitee_id' => $request->invitee_id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send invitation. Please try again later.'
            ], 500);
        }
    }
    public function acceptInvitation(ProcessInvitationRequest $request, JoinRequestModel $invitation): JsonResponse
    {
        try {
            // Authorization already checked in ProcessInvitationRequest

            if ($invitation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invitation already processed.'
                ], 400);
            }

            $project = $invitation->project;

            // Check project status (active only)
            if ($project->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot join this project because it is not active.'
                ], 403);
            }

            DB::beginTransaction();

            $role = $invitation->role ?? 'user';
            $project->addUser($invitation->receiver_id, $role);

            $invitation->update([
                'status' => 'approved',
                'responded_at' => now(),
                'responded_by' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation accepted. You are now a member of the project.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Accept invitation failed: ' . $e->getMessage(), [
                'invitation_id' => $invitation->id,
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept invitation. Please try again later.'
            ], 500);
        }
    }

    public function rejectInvitation(ProcessInvitationRequest $request, JoinRequestModel $invitation): JsonResponse
    {
        try {
            if ($invitation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invitation already processed.'
                ], 400);
            }

            DB::beginTransaction();

            $invitation->update([
                'status' => 'rejected',
                'responded_at' => now(),
                'responded_by' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation rejected.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reject invitation failed: ' . $e->getMessage(), [
                'invitation_id' => $invitation->id,
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject invitation. Please try again later.'
            ], 500);
        }
    }
}
