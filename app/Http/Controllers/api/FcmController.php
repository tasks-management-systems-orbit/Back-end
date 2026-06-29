<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcmController extends Controller
{
    use ApiResponseTrait;

    /**
     * Register or update a device token for the authenticated user.
     * Called by Flutter after obtaining an FCM token (and on token refresh).
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:500',
            'device_type' => 'nullable|string|in:android,ios,web|max:50',
            'device_name' => 'nullable|string|max:255',
        ]);

        FcmToken::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'token' => $validated['token'],
            ],
            [
                'device_type' => $validated['device_type'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return $this->successResponse(null, 'Token registered successfully');
    }

    /**
     * Unregister a device token (e.g., on logout).
     */
    public function unregister(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:500',
        ]);

        FcmToken::where('user_id', Auth::id())
            ->where('token', $validated['token'])
            ->delete();

        return $this->successResponse(null, 'Token unregistered successfully');
    }

    /**
     * List the caller's registered devices.
     * Useful for a "manage devices" UI and for the Flutter team to build
     * diagnostics screens.
     */
    public function index(Request $request)
    {
        $tokens = FcmToken::where('user_id', Auth::id())
            ->orderByDesc('last_used_at')
            ->get(['id', 'device_type', 'device_name', 'last_used_at', 'created_at']);

        return $this->successResponse(['tokens' => $tokens]);
    }
}
