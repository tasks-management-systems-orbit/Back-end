<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ProjectNotificationEvent
{
    use Dispatchable;

    public function __construct(
        public array $userIds,
        public string $scenario,
        public Project $project,
        public ?User $actor = null,
        public mixed $extra = null,
    ) {}
}
