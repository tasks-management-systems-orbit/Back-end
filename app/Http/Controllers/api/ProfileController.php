<?php
// app/Http/Controllers/Api/ProfileController.php

namespace app\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\ProfileCollection;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index(Request $request): ProfileCollection
    {
        $perPage = $request->get('per_page', 15);

        $profiles = Profile::with('user')
            ->where('is_public', true) // Only show public profiles
            ->when($request->has('theme'), function ($query) use ($request) {
                $query->where('theme', $request->theme);
            })
            ->when($request->has('language'), function ($query) use ($request) {
                $query->where('language', $request->language);
            })
            ->when($request->has('job_title'), function ($query) use ($request) {
                $query->where('job_title', 'LIKE', '%' . $request->job_title . '%');
            })
            ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_direction', 'desc'))
            ->paginate($perPage);

        return new ProfileCollection($profiles);
    }

    public function store(StoreProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $validated = $request->validated();

            if (isset($validated['is_public']) && !$validated['is_public']) {
                $validated['allow_messages'] = false;
                $validated['allow_invitation_requests'] = false;
            }

            $profile = $user->profile;

            if ($profile) {
                $profile->update($validated);
            } else {
                $validated['user_id'] = $user->id;
                $profile = Profile::create($validated);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile saved successfully',
                'data' => new ProfileResource($profile->load('user'))
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Profile $profile): ProfileResource|JsonResponse
    {
        $user = $request->user();
        $isOwner = $user && $user->id === $profile->user_id;

        if (!$profile->is_public && !$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'This profile is private'
            ], 403);
        }

        return new ProfileResource($profile->load('user'));
    }

    public function update(UpdateProfileRequest $request, Profile $profile): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // If setting profile to private, disable messages and invitations
            if (isset($validated['is_public']) && !$validated['is_public']) {
                $validated['allow_messages'] = false;
                $validated['allow_invitation_requests'] = false;
            }

            $profile->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new ProfileResource($profile->fresh()->load('user'))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Profile $profile): JsonResponse
    {
        try {
            DB::beginTransaction();

            $profile->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function myProfile(Request $request): ProfileResource|JsonResponse
    {
        $profile = $request->user()->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'No profile found for the current user'
            ], 404);
        }

        return new ProfileResource($profile->load('user'));
    }





    public function blockUser(Request $request, $userId): JsonResponse
    {
        try {
            $user = $request->user();
            $userToBlock = \app\Models\User::findOrFail($userId);

            if ($user->id === $userToBlock->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot block yourself'
                ], 400);
            }

            $user->blockUser($userToBlock, $request->get('reason'));

            return response()->json([
                'success' => true,
                'message' => "User blocked successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while blocking the user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unblockUser(Request $request, $userId): JsonResponse
    {
        try {
            $user = $request->user();
            $userToUnblock = \app\Models\User::findOrFail($userId);

            $user->unblockUser($userToUnblock);

            return response()->json([
                'success' => true,
                'message' => "User unblocked successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while unblocking the user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBlockedUsers(Request $request): JsonResponse
    {
        $blockedUsers = $request->user()
            ->blockedUsers()
            ->with('blockedUser')
            ->get()
            ->map(function ($block) {
                return [
                    'id' => $block->blockedUser->id,
                    'name' => $block->blockedUser->name,
                    'email' => $block->blockedUser->email,
                    'blocked_at' => $block->created_at,
                    'reason' => $block->reason,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $blockedUsers
        ]);
    }

    public function canSendMessage(Request $request, Profile $profile): JsonResponse
    {
        $currentUser = $request->user();


        if (!$profile->is_public) {
            return response()->json(['can_message' => false, 'reason' => 'Profile is private'], 403);
        }

        if (!$profile->allow_messages) {
            return response()->json(['can_message' => false, 'reason' => "User has disabled messages"], 403);
        }

        if ($currentUser->isBlocking($profile->user)) {
            return response()->json(['can_message' => false, 'reason' => 'You have blocked this user'], 403);
        }

        if ($profile->user->isBlocking($currentUser)) {
            return response()->json(['can_message' => false, 'reason' => 'You are blocked by this user'], 403);
        }

        return response()->json(['can_message' => true]);
    }

    public function canSendInvitation(Request $request, Profile $profile): JsonResponse
    {
        $currentUser = $request->user();

        if (!$profile->is_public) {
            return response()->json(['can_invite' => false, 'reason' => 'Profile is private'], 403);
        }

        if (!$profile->allow_invitation_requests) {
            return response()->json(['can_invite' => false, 'reason' => "User has disabled invitation requests"], 403);
        }

        if ($currentUser->isBlocking($profile->user)) {
            return response()->json(['can_invite' => false, 'reason' => 'You have blocked this user'], 403);
        }

        if ($profile->user->isBlocking($currentUser)) {
            return response()->json(['can_invite' => false, 'reason' => 'You are blocked by this user'], 403);
        }

        return response()->json(['can_invite' => true]);
    }



    public function addSkill(Request $request, Profile $profile): JsonResponse
    {
        $request->validate([
            'skill' => 'required|string|max:100',
            'rating' => 'sometimes|integer|min:1|max:10'
        ]);

        $rating = $request->get('rating', 5);
        $added = $profile->addSkill($request->skill, $rating);

        if (!$added) {
            return response()->json([
                'success' => false,
                'message' => 'Skill already exists'
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Skill added successfully',
            'data' => new ProfileResource($profile->fresh()->load('user'))
        ]);
    }

    public function updateSkillRating(Request $request, Profile $profile, string $skill): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:10'
        ]);

        $updated = $profile->updateSkillRating($skill, $request->rating);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Skill not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Skill rating updated successfully',
            'data' => new ProfileResource($profile->fresh()->load('user'))
        ]);
    }

    public function removeSkill(Profile $profile, string $skill): JsonResponse
    {
        $removed = $profile->removeSkill($skill);

        if (!$removed) {
            return response()->json([
                'success' => false,
                'message' => 'Skill not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Skill removed successfully',
            'data' => new ProfileResource($profile->fresh()->load('user'))
        ]);
    }

    public function getSkills(Profile $profile): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'skills' => $profile->skills,
                'average_rating' => $profile->average_skill_rating,
                'top_skills' => $profile->top_skills
            ]
        ]);
    }
}
