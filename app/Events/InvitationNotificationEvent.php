<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use App\Models\Request;
use Illuminate\Foundation\Events\Dispatchable;

class InvitationNotificationEvent
{
    use Dispatchable;

    public function __construct(
        public array $userIds,
        public string $scenario,
        public Request $request,
        public Project $project,
        public ?User $actor = null,
        public mixed $extra = null,
    ) {}
}
