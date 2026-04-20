<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isMember = $this->created_by === $user?->id || $this->users->contains('id', $user?->id);

        if ($this->visibility === 'private' && !$isMember) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'image' => $this->image,
                'status' => $this->status,
                'status_label' => $this->status_label,
                'visibility' => $this->visibility,
                'visibility_label' => $this->visibility_label,
                'is_private' => true,
                'message' => 'This project is private. You do not have access to its details.'
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'visibility' => $this->visibility,
            'visibility_label' => $this->visibility_label,
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'members_count' => $this->whenCounted('members'),
            'tasks_count' => $this->whenCounted('tasks'),
            'user_role' => $this->user_role ?? null,
            'is_owner' => $this->is_owner ?? false,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
