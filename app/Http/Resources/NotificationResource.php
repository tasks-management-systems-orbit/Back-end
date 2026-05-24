<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'icon' => $this->icon,
            'action_url' => $this->action_url,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'data' => $this->data,
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
