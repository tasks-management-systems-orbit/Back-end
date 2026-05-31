<?php

namespace App\Listeners;

use App\Events\NotificationSent;
use App\Jobs\SendEmailNotificationJob;

class SendEmailNotification
{
    public function handle(NotificationSent $event): void
    {
        $notification = $event->notification;

        SendEmailNotificationJob::dispatch(
            $notification->user_id,
            $notification->title,
            $notification->message,
        );
    }
}
