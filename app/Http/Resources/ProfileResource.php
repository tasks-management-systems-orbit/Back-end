<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'phone' => $this->phone,
            'bio' => $this->bio,
            'job_title' => $this->job_title,
            'skills' => $this->skills,
            'avatar' => $this->avatar,
            'location' => $this->location,
            'twitter_url' => $this->twitter_url,
            'alternative_email' => $this->alternative_email,
            'github_url' => $this->github_url,
            'portfolio_url' => $this->portfolio_url,
            'linkedin_url' => $this->linkedin_url,
            'cv_url' => $this->cv_url,
            'language' => $this->language,
            'theme' => $this->theme,
            'is_public' => $this->is_public,
            'projects_count' => $this->projects_count,
            'tasks_completed' => $this->tasks_completed,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
