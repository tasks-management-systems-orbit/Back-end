<?php

namespace App\Listeners;

use App\Events\ManagerTaskCompleted;
use App\Events\TaskNotificationEvent;

class NotifyManagerTaskCompleted
{
    public function handle(ManagerTaskCompleted $event): void
    {
        $task = $event->task;

        if ($task->group && $task->group->manager_id) {
            TaskNotificationEvent::dispatch(
                userIds: [$task->group->manager_id],
                scenario: 'completed',
                task: $task,
            );
        }
    }
}
