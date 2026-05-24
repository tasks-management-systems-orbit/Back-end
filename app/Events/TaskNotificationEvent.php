<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class TaskNotificationEvent
{
    use Dispatchable;

    public function __construct(
        public array $userIds,
        public string $scenario,
        public Task $task,
        public ?User $actor = null,
        public mixed $extra = null,
    ) {}
}
