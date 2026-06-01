<?php

namespace App\Listeners;

use App\Events\InvitationNotificationEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyInvitationParticipants implements ShouldQueue
{
    public function handle(InvitationNotificationEvent $event): void
    {
        $svc = app(NotificationService::class);

        [$title, $message, $icon, $url] = match ($event->scenario) {
            'invitation_sent' => [
                'Project Invitation',
                "You have been invited to join project: {$event->project->name}",
                '✉️',
                "/projects/{$event->project->id}",
            ],
            'join_request_received' => [
                'Join Request',
                $event->actor
                    ? "{$event->actor->name} wants to join project: {$event->project->name}"
                    : "Someone wants to join project: {$event->project->name}",
                '👋',
                "/projects/{$event->project->id}",
            ],
            'invitation_accepted' => [
                'Invitation Accepted',
                $event->actor
                    ? "{$event->actor->name} accepted your invitation to join {$event->project->name}"
                    : "Your invitation to join {$event->project->name} has been accepted",
                '👍',
                "/projects/{$event->project->id}",
            ],
            'invitation_rejected' => [
                'Invitation Rejected',
                $event->actor
                    ? "{$event->actor->name} rejected your invitation to join {$event->project->name}"
                    : "Your invitation to join {$event->project->name} has been rejected",
                '👎',
                "/projects/{$event->project->id}",
            ],
            'join_request_approved' => [
                'Join Request Approved',
                "Your request to join \"{$event->project->name}\" has been approved",
                '✅',
                "/projects/{$event->project->id}",
            ],
            'join_request_rejected' => [
                'Join Request Rejected',
                "Your request to join \"{$event->project->name}\" has been rejected",
                '❌',
                "/projects/{$event->project->id}",
            ],
            default => [
                'Request Update',
                "Update on request for project: {$event->project->name}",
                '📌',
                "/projects/{$event->project->id}",
            ],
        };

        $svc->sendToMany($event->userIds, $title, $message, 'request', null, $url, $icon);
    }
}
