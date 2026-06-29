<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Show the notification compose form.
     * user_ids[] is passed from the users list checkboxes.
     */
    public function create(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $users = User::whereIn('id', $request->user_ids)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $filters = $this->preservedFilters($request);

        return view('admin.notifications.create', compact('users', 'filters'));
    }

    /**
     * Send the notification to all selected users.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'type' => 'nullable|string|in:info,success,warning,error,urgent',
        ]);

        $type = $validated['type'] ?? 'info';
        $admin = Auth::guard('admin')->user();

        $this->notificationService->sendToMany(
            $validated['user_ids'],
            $validated['title'],
            $validated['message'],
            $type,
            ['sent_by' => $admin->name, 'admin_id' => $admin->id]
        );

        return redirect()
            ->route('admin.users.index', $this->preservedFilters($request))
            ->with('success', 'Notification sent to ' . count($validated['user_ids']) . ' user(s) successfully.');
    }

    /**
     * Whitelist of users-list filter params to forward from the compose page
     * back to the index, so the admin returns to the same filtered view they
     * were on before opening the notification form.
     */
    private function preservedFilters(Request $request): array
    {
        $keys = ['search', 'status', 'email_verified', 'sort', 'per_page', 'date_from', 'date_to', 'page'];
        $filters = [];
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                $filters[$key] = $request->input($key);
            }
        }
        return $filters;
    }
}
