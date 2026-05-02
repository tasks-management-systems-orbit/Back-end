<?php

namespace app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\JoinRequest\SendJoinRequest;
use App\Http\Requests\JoinRequest\ProcessJoinRequest;
use App\Http\Requests\Invitation\SendInvitationRequest;
use App\Http\Requests\Invitation\ProcessInvitationRequest;
use App\Models\Project;
use App\Models\Request as JoinRequestModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    // =============  (Join Requests) =============

    public function listJoinRequests(Request $request, Project $project): JsonResponse
    {
        if (!$project->isOwner($request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $requests = $project->joinRequests()->with('sender')->latest()->get();
        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function sendJoinRequest(SendJoinRequest $request, Project $project): JsonResponse
    {
        $existing = JoinRequestModel::where('project_id', $project->id)
            ->where('sender_id', $request->user()->id)
            ->where('type', 'join_request')
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'You already have a pending request for this project.'], 409);
        }

        $joinRequest = JoinRequestModel::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $project->created_by,
            'project_id' => $project->id,
            'type' => 'join_request',
            'status' => 'pending',
            'message' => $request->input('message'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Join request sent.',
            'data' => $joinRequest
        ], 201);
    }

    public function processJoinRequest(ProcessJoinRequest $request, Project $project, JoinRequestModel $joinRequest): JsonResponse
    {
        if ($joinRequest->project_id !== $project->id || $joinRequest->type !== 'join_request') {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 404);
        }

        if ($joinRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request already processed.'], 400);
        }

        if ($project->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Project is completed, cannot add new members.'], 403);
        }

        $newStatus = $request->status;

        DB::beginTransaction();
        try {
            if ($newStatus === 'approved') {
                $role = $request->input('role', 'user');
                $project->addUser($joinRequest->sender_id, $role);

                $joinRequest->update([
                    'status' => 'approved',
                    'responded_at' => now(),
                    'responded_by' => $request->user()->id,
                ]);

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Join request approved. User added to project.']);
            }

            if ($newStatus === 'rejected') {
                $joinRequest->update([
                    'status' => 'rejected',
                    'responded_at' => now(),
                    'responded_by' => $request->user()->id,
                ]);

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Join request rejected.']);
            }

            return response()->json(['success' => false, 'message' => 'Invalid status.'], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to process request.'], 500);
        }
    }




    // =============  (Invitations) =============

    public function myInvitations(Request $request): JsonResponse
    {
        $invitations = JoinRequestModel::where('receiver_id', $request->user()->id)
            ->where('type', 'invitation')
            ->where('status', 'pending')
            ->with(['project', 'sender'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $invitations]);
    }

    public function sendInvitation(SendInvitationRequest $request, Project $project): JsonResponse
    {
        $inviteeId = $request->invitee_id;

        $existing = JoinRequestModel::where('project_id', $project->id)
            ->where('receiver_id', $inviteeId)
            ->where('type', 'invitation')
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Invitation already sent to this user.'], 409);
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

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent.',
            'data' => $invitation
        ], 201);
    }

    public function acceptInvitation(ProcessInvitationRequest $request, JoinRequestModel $invitation): JsonResponse
    {
        if ($invitation->receiver_id !== $request->user()->id || $invitation->type !== 'invitation') {
            return response()->json(['success' => false, 'message' => 'Unauthorized or invalid invitation.'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Invitation already processed.'], 400);
        }

        $project = $invitation->project;

        
        if ($project->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Cannot join a completed project.'], 403);
        }



        DB::beginTransaction();
        try {
            $role = $invitation->role ?? 'user';
            $project->addUser($invitation->receiver_id, $role);

            $invitation->update([
                'status' => 'approved',
                'responded_at' => now(),
                'responded_by' => $request->user()->id,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Invitation accepted. You are now a member of the project.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to accept invitation.'], 500);
        }
    }

    public function rejectInvitation(ProcessInvitationRequest $request, JoinRequestModel $invitation): JsonResponse
    {
        if ($invitation->receiver_id !== $request->user()->id || $invitation->type !== 'invitation') {
            return response()->json(['success' => false, 'message' => 'Unauthorized or invalid invitation.'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Invitation already processed.'], 400);
        }

        $invitation->update([
            'status' => 'rejected',
            'responded_at' => now(),
            'responded_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Invitation rejected.']);
    }
}
