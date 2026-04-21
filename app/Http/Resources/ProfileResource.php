<?php

namespace app\Http\Resources;

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
                    'email' => $this->when(
                        request()->user() && request()->user()->id === $this->user_id,
                        $this->user->email
                    ),
                ];
            }),
            'phone' => $this->phone,
            'bio' => $this->bio,
            'job_title' => $this->job_title,
            'skills' => $this->skills,
            'avatar' => $this->avatar,
            'location' => $this->location,
            'alternative_email' => $this->alternative_email,

            // Social links
            'twitter_url' => $this->twitter_url,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'youtube_url' => $this->youtube_url,
            'github_url' => $this->github_url,
            'portfolio_url' => $this->portfolio_url,
            'linkedin_url' => $this->linkedin_url,
            'cv_url' => $this->cv_url,

            // Preferences
            'language' => $this->language,
            'theme' => $this->theme,

            // Privacy settings
            'is_public' => $this->is_public,
            'allow_messages' => $this->allow_messages,
            'allow_invitation_requests' => $this->allow_invitation_requests,

            // Stats
            'projects_count' => $this->projects_count,
            'tasks_completed' => $this->tasks_completed,
            'report_count' => $this->report_count,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
