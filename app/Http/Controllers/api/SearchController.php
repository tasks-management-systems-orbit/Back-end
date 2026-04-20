<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Models\Project;
use App\Models\User;

class SearchController extends Controller
{
    public function search(SearchRequest $request)
    {
        $query = $request->input('q');

        $users = $this->searchUsers($query);

        $projects = $this->searchProjects($query);

        return response()->json([
            'success' => true,
            'query' => $query,
            'data' => [
                'users' => $users,
                'projects' => $projects,
            ],
            'total' => count($users) + count($projects),
        ]);
    }

    private function searchUsers($query)
    {
        $keywords = explode(' ', $query);

        $users = User::query()
            ->with('profile')
            ->where(function ($q) use ($keywords, $query) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('username', 'LIKE', "%{$keyword}%");
                }
                $q->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->get();

        foreach ($users as $user) {
            $user->match_score = $this->calculateUserMatchScore($user, $query);
        }

        return $users->sortByDesc('match_score')
            ->take(5)
            ->values()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->profile?->avatar,
                    'job_title' => $user->profile?->job_title,
                    'match_score' => $user->match_score,
                ];
            });
    }

    private function searchProjects($query)
    {
        $keywords = explode(' ', $query);

        $projects = Project::query()
            ->where(function ($q) use ($keywords, $query) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'LIKE', "%{$keyword}%");
                }
                $q->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->get();

        foreach ($projects as $project) {
            $project->match_score = $this->calculateProjectMatchScore($project, $query);
        }

        return $projects->sortByDesc('match_score')
            ->take(5)
            ->values()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'image' => $project->image,
                    'status' => $project->status,
                    'visibility' => $project->visibility,
                    'match_score' => $project->match_score,
                ];
            });
    }

    private function calculateUserMatchScore($user, $query)
    {
        $score = 0;
        $lowerQuery = strtolower($query);
        $lowerName = strtolower($user->name);
        $lowerUsername = strtolower($user->username);

        if ($lowerName === $lowerQuery || $lowerUsername === $lowerQuery) {
            $score += 100;
        }
        elseif (str_starts_with($lowerName, $lowerQuery) || str_starts_with($lowerUsername, $lowerQuery)) {
            $score += 80;
        }
        elseif (str_contains($lowerName, " {$lowerQuery} ") || str_contains($lowerUsername, " {$lowerQuery} ")) {
            $score += 60;
        }
        elseif (str_contains($lowerName, $lowerQuery) || str_contains($lowerUsername, $lowerQuery)) {
            $score += 40;
        }

        if ($user->profile && str_contains(strtolower($user->profile->job_title ?? ''), $lowerQuery)) {
            $score += 20;
        }

        return $score;
    }

    private function calculateProjectMatchScore($project, $query)
    {
        $score = 0;
        $lowerQuery = strtolower($query);
        $lowerName = strtolower($project->name);

        if ($lowerName === $lowerQuery) {
            $score += 100;
        }
        elseif (str_starts_with($lowerName, $lowerQuery)) {
            $score += 80;
        }
        elseif (str_contains($lowerName, " {$lowerQuery} ")) {
            $score += 60;
        }
        elseif (str_contains($lowerName, $lowerQuery)) {
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
