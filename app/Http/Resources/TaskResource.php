<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'status_id' => $this->status_id,
            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'position' => $this->status->position,
                ];
            }),
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'priority_color' => $this->priority_color,
            'due_date' => $this->due_date?->toISOString(),
            'due_date_formatted' => $this->due_date_formatted,
            'is_overdue' => $this->is_overdue,
            'is_blocked' => $this->is_blocked,
            'can_be_completed' => $this->can_be_completed,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'avatar' => $this->creator->profile?->avatar,
                ];
            }),
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', function () {
                return [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                    'avatar' => $this->assignee->profile?->avatar,
                ];
            }),
            'assignees' => $this->whenLoaded('assignments', function () {
                return $this->assignments->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->profile?->avatar,
                    ];
                });
            }),
            'assignments_count' => $this->assignments_count,
            'position' => $this->position,
            'is_completed' => $this->isCompleted(),
            'completed_at' => $this->completed_at?->toISOString(),
            'dependencies_count' => $this->dependencies_count,
            'dependents_count' => $this->dependents_count,
            'comments_count' => $this->whenCounted('comments', $this->comments_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}   
