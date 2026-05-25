<?php

namespace App\Listeners;

use App\Events\ProjectNotificationEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyProjectMembers implements ShouldQueue
{
    public function handle(ProjectNotificationEvent $event): void
    {
        $svc = app(NotificationService::class);

        [$title, $message, $icon, $url] = match ($event->scenario) {
            'user_added' => [
                'Added to Project',
                "You have been added to project: {$event->project->name}",
                '👋',
                "/projects/{$event->project->id}",
            ],
            'user_removed' => [
                'Removed from Project',
                "You have been removed from project: {$event->project->name}",
                '🚫',
                "/projects/{$event->project->id}",
            ],
            'role_changed' => [
                'Role Changed',
                $event->extra && isset($event->extra['role'])
                    ? "Your role in \"{$event->project->name}\" has been changed to {$event->extra['role']}"
                    : "Your role in \"{$event->project->name}\" has been changed",
                '🔄',
                "/projects/{$event->project->id}",
            ],
            'ownership_transferred' => [
                'Ownership Transferred',
                $event->actor
                    ? "{$event->actor->name} transferred ownership of \"{$event->project->name}\""
                    : "Ownership of \"{$event->project->name}\" has been transferred",
                '👑',
                "/projects/{$event->project->id}",
            ],
            'status_changed' => [
                'Project Status Changed',
                $event->extra && isset($event->extra['status'])
                    ? "Project \"{$event->project->name}\" status changed to {$event->extra['status']}"
                    : "Project \"{$event->project->name}\" status has changed",
                '📊',
                "/projects/{$event->project->id}",
            ],
            'user_left' => [
                'Member Left',
                $event->actor
                    ? "{$event->actor->name} has left project: {$event->project->name}"
                    : "A member has left project: {$event->project->name}",
                '🚪',
                "/projects/{$event->project->id}",
            ],
            'project_commented' => [
                'New Project Comment',
                $event->actor
                    ? "{$event->actor->name} commented on project: {$event->project->name}"
                    : "A comment was added to project: {$event->project->name}",
                '💬',
                "/projects/{$event->project->id}",
            ],
            default => [
                'Project Update',
                "Update on project: {$event->project->name}",
                '📌',
                "/projects/{$event->project->id}",
            ],
        };

        $svc->sendToMany($event->userIds, $title, $message, 'project', null, $url, $icon);
    }
}
