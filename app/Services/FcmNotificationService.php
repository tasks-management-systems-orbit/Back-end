<?php

namespace App\Services;

use App\Models\FcmToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Throwable;

class FcmNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = app('firebase.messaging');
    }

    /**
     * Send push notification to a single user across all their active devices.
     * Returns the number of successful sends.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): int
    {
        try {
            $tokens = FcmToken::where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereNull('last_used_at')
                      ->orWhere('last_used_at', '>=', now()->subMonths(6));
                })
                ->pluck('token')
                ->toArray();
        } catch (Throwable $e) {
            Log::error('FCM token lookup failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }

        if (empty($tokens)) {
            return 0;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send push notification to multiple users across all their active devices.
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): int
    {
        if (empty($userIds)) {
            return 0;
        }

        try {
            $tokens = FcmToken::whereIn('user_id', $userIds)
                ->where(function ($q) {
                    $q->whereNull('last_used_at')
                      ->orWhere('last_used_at', '>=', now()->subMonths(6));
                })
                ->pluck('token')
                ->toArray();
        } catch (Throwable $e) {
            Log::error('FCM token lookup failed', [
                'user_ids' => $userIds,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }

        if (empty($tokens)) {
            return 0;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send to an array of device tokens. FCM HTTP v1 supports up to 500 per
     * multicast call; we batch automatically. On each successful delivery we
     * also update `last_used_at` so the 6-month filter in sendToUser(s)
     * remains meaningful.
     */
    protected function sendToTokens(array $tokens, string $title, string $body, array $data = []): int
    {
        $sentCount = 0;
        $successfulTokens = [];
        $chunks = array_chunk($tokens, 500);

        foreach ($chunks as $chunk) {
            try {
                $message = CloudMessage::new()
                    ->withNotification(FcmNotification::create($title, $body))
                    ->withData($data);

                $result = $this->messaging->sendMulticast($message, $chunk);

                foreach ($result->successes()->getItems() as $sent) {
                    $successfulTokens[] = $sent->target()->value();
                    $sentCount++;
                }

                // Clean up invalid (unregistered) tokens
                if ($result->hasFailures()) {
                    foreach ($result->failures()->getItems() as $failure) {
                        if ($failure->messageWasSentToUnknownToken()) {
                            $badToken = $failure->target()->value();
                            FcmToken::where('token', $badToken)->delete();
                            Log::info('Removed unregistered FCM token', [
                                'token_prefix' => substr($badToken, 0, 20) . '...',
                            ]);
                        }
                    }
                }
            } catch (Throwable $e) {
                Log::error('FCM sendMulticast failed', [
                    'token_count' => count($chunk),
                    'error' => $e->getMessage(),
                ]);
                // Continue with the next chunk rather than failing the whole job.
            }
        }

        // Refresh activity timestamp on the tokens we actually reached.
        // This is what makes the 6-month filter in sendToUser() meaningful.
        if (!empty($successfulTokens)) {
            FcmToken::whereIn('token', $successfulTokens)
                ->update(['last_used_at' => now()]);
        }

        return $sentCount;
    }
}
