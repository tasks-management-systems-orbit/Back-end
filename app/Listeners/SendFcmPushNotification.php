<?php

namespace App\Listeners;

use App\Events\NotificationSent;
use App\Services\FcmNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFcmPushNotification implements ShouldQueue
{
    public function __construct(
        protected FcmNotificationService $fcm
    ) {}

    public function handle(NotificationSent $event): void
    {
        $notification = $event->notification;

        // All data values must be strings — FCM HTTP v1 data payload constraint.
        $data = [
            'notification_id' => (string) $notification->id,
            'type'            => (string) ($notification->type ?? 'info'),
            'action_url'      => (string) ($notification->action_url ?? ''),
            'icon'            => (string) ($notification->icon ?? ''),
        ];

        $this->fcm->sendToUser(
            $notification->user_id,
            $notification->title,
            $notification->message,
            $data
        );
    }
}
