<?php

namespace App\Http\Controllers\Api;

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
            ->when($request->has('is_public'), function ($query) use ($request) {
                $query->where('is_public', $request->boolean('is_public'));
            })
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

            $profile = Profile::create($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile created successfully',
                'data' => new ProfileResource($profile->load('user'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Profile $profile): ProfileResource
    {
        return new ProfileResource($profile->load('user'));
    }

    public function update(UpdateProfileRequest $request, Profile $profile): JsonResponse
    {
        try {
            DB::beginTransaction();

            $profile->update($request->validated());

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

    return $profile
        ? new ProfileResource($profile->load('user'))
        : response()->json([
            'success' => false,
            'message' => 'No profile found for the current user'
        ], 404);
}

    public function updateStats(Request $request, Profile $profile): JsonResponse
    {
        $request->validate([
            'projects_count' => 'sometimes|integer|min:0',
            'tasks_completed' => 'sometimes|integer|min:0',
        ]);

        $profile->update($request->only(['projects_count', 'tasks_completed']));

        return response()->json([
            'success' => true,
            'message' => 'Statistics updated successfully',
            'data' => new ProfileResource($profile)
        ]);
    }

    public function search(Request $request): ProfileCollection
    {
        $searchTerm = $request->get('q');
        $perPage = $request->get('per_page', 15);

        $profiles = Profile::with('user')
            ->where(function ($query) use ($searchTerm) {
                $query->where('job_title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('location', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('bio', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('skills', 'LIKE', "%{$searchTerm}%");
            })
            ->paginate($perPage);

        return new ProfileCollection($profiles);
    }
}
