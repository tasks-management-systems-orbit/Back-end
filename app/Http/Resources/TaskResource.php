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
            'priority_label' => $this->getPriorityLabel(),
            'priority_color' => $this->getPriorityColor(),
            'due_date' => $this->due_date?->toISOString(),
            'due_date_formatted' => $this->due_date?->format('Y-m-d'),
            'is_overdue' => $this->isOverdue(),
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
            'position' => $this->position,
            'is_completed' => $this->isCompleted(),
            'completed_at' => $this->completed_at?->toISOString(),
            'dependencies_count' => $this->whenCounted('dependencies'),
            'comments_count' => $this->whenCounted('comments'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function getPriorityLabel(): string
    {
        return match ($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Medium',
        };
    }

    private function getPriorityColor(): string
    {
        return match ($this->priority) {
            'low' => '#10B981',     // Green
            'medium' => '#F59E0B',  // Yellow
            'high' => '#EF4444',    // Red
            'urgent' => '#8B5CF6',  // Purple
            default => '#6B7280',   // Gray
        };
    }
}
