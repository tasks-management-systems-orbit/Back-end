<?php

namespace app\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    /**
     * Perform search based on query string.
     * Supports:
     * - @username  : search only users
     * - #project   : search only projects
     * - default    : search both users and projects
     */
    public function search(SearchRequest $request): JsonResponse
    {
        try {
            $query = $request->input('q');
            $originalQuery = $query;
            $searchType = 'both';
            $limit = $request->input('limit', 5); // default 5

            // Handle special limit values: 'all' or 0 means unlimited
            $unlimited = ($limit === 'all' || $limit == 0);
            $numericLimit = $unlimited ? null : (int) $limit;

            if (str_starts_with($query, '@')) {
                $searchType = 'users';
                $query = ltrim($query, '@');
            } elseif (str_starts_with($query, '#')) {
                $searchType = 'projects';
                $query = ltrim($query, '#');
            }

            if (empty(trim($query))) {
                return $this->emptyResponse($originalQuery);
            }

            $users = [];
            $projects = [];

            if ($searchType === 'both' || $searchType === 'users') {
                $users = $this->searchUsers($query, $numericLimit);
            }
            if ($searchType === 'both' || $searchType === 'projects') {
                $projects = $this->searchProjects($query, $numericLimit);
            }

            $responseData = ['query' => $originalQuery];

            if ($searchType === 'both') {
                $responseData['data'] = ['users' => $users, 'projects' => $projects];
                $responseData['total'] = count($users) + count($projects);
            } elseif ($searchType === 'users') {
                $responseData['data'] = ['users' => $users];
                $responseData['total'] = count($users);
            } else {
                $responseData['data'] = ['projects' => $projects];
                $responseData['total'] = count($projects);
            }

            return response()->json(['success' => true, ...$responseData]);
        } catch (\Exception $e) {
            Log::error('Search failed: ' . $e->getMessage(), ['query' => $request->input('q'), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'An error occurred while searching.'], 500);
        }
    }

    private function emptyResponse(string $originalQuery): JsonResponse
    {
        return response()->json(['success' => true, 'query' => $originalQuery, 'data' => [], 'total' => 0]);
    }

    private function searchUsers(string $query, ?int $limit): array
    {
        $keywords = explode(' ', $query);
        $usersQuery = User::with('profile')
            ->where(function ($q) use ($keywords, $query) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('username', 'LIKE', "%{$keyword}%");
                }
                $q->orWhere('email', 'LIKE', "%{$query}%");
            });

        $users = $usersQuery->get();
        /** @var \App\Models\User $user */
        foreach ($users as $user) {
            $user->match_score = $this->calculateUserMatchScore($user, $query);
        }

        $sorted = $users->sortByDesc('match_score');
        if ($limit !== null) {
            $sorted = $sorted->take($limit);
        }

        return $sorted->values()->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar' => $user->profile?->avatar,
            'job_title' => $user->profile?->job_title,
            'match_score' => $user->match_score,
        ])->toArray();
    }

    private function searchProjects(string $query, ?int $limit): array
    {
        $keywords = explode(' ', $query);
        $projectsQuery = Project::query()
            ->where(function ($q) use ($keywords, $query) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'LIKE', "%{$keyword}%");
                }
                $q->orWhere('description', 'LIKE', "%{$query}%");
            });

        $projects = $projectsQuery->get();
        /** @var \App\Models\Project $project */
        foreach ($projects as $project) {
            $project->match_score = $this->calculateProjectMatchScore($project, $query);
        }

        $sorted = $projects->sortByDesc('match_score');
        if ($limit !== null) {
            $sorted = $sorted->take($limit);
        }

        return $sorted->values()->map(fn($project) => [
            'id' => $project->id,
            'name' => $project->name,
            'image' => $project->image,
            'status' => $project->status,
            'visibility' => $project->visibility,
            'match_score' => $project->match_score,
        ])->toArray();
    }

    /**
     * Calculate relevance score for user search results.
     */
    private function calculateUserMatchScore(User $user, string $query): int
    {
        $score = 0;
        $lowerQuery = strtolower($query);
        $lowerName = strtolower($user->name);
        $lowerUsername = strtolower($user->username);

        if ($lowerName === $lowerQuery || $lowerUsername === $lowerQuery) {
            $score += 100;
        } elseif (str_starts_with($lowerName, $lowerQuery) || str_starts_with($lowerUsername, $lowerQuery)) {
            $score += 80;
        } elseif (str_contains($lowerName, " {$lowerQuery} ") || str_contains($lowerUsername, " {$lowerQuery} ")) {
            $score += 60;
        } elseif (str_contains($lowerName, $lowerQuery) || str_contains($lowerUsername, $lowerQuery)) {
            $score += 40;
        }

        if ($user->profile && str_contains(strtolower($user->profile->job_title ?? ''), $lowerQuery)) {
            $score += 20;
        }

        return $score;
    }

    /**
     * Calculate relevance score for project search results.
     */
    private function calculateProjectMatchScore(Project $project, string $query): int
    {
        $score = 0;
        $lowerQuery = strtolower($query);
        $lowerName = strtolower($project->name);

        if ($lowerName === $lowerQuery) {
            $score += 100;
        } elseif (str_starts_with($lowerName, $lowerQuery)) {
            $score += 80;
        } elseif (str_contains($lowerName, " {$lowerQuery} ")) {
            $score += 60;
        } elseif (str_contains($lowerName, $lowerQuery)) {
            $score += 40;
        }

        if ($project->description && str_contains(strtolower($project->description), $lowerQuery)) {
            $score += 15;
        }

        if ($project->status === 'active') {
            $score += 10;
        }

        return $score;
    }
}
