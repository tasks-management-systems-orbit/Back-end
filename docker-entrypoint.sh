#!/bin/sh
set -e

# 1. Run migrations
# The --force flag is required for production
echo "Running migrations..."
php artisan migrate --force

# 2. Optimize Laravel for production
echo "Caching configuration, routes, views, and events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 3. Start the queue worker in the background.
#
#    SendFcmPushNotification implements ShouldQueue and
#    QUEUE_CONNECTION=database, so without a worker, FCM pushes (and
#    the SendEmailNotificationJob) pile up in the `jobs` table forever
#    and are never delivered.
#
#    On Render's free tier we cannot run a separate Background Worker
#    service (that requires the Starter plan), so the worker is a
#    backgrounded supervised loop inside the same container that runs
#    Apache. The loop restarts the worker on any exit. When the
#    container is restarted, the loop is restarted with it.
#
#    When you upgrade to a paid tier, move this back to a dedicated
#    Background Worker service on Render and remove this block.
(
    while true; do
        php artisan queue:work \
            --tries=3 \
            --backoff=10 \
            --max-time=3600 \
            --sleep=1 \
            --timeout=60 \
            --quiet
        echo "Queue worker exited; restarting in 2s..."
        sleep 2
    done
) &

# 4. Start the web server (foreground; receives SIGTERM from docker stop)
exec "$@"
