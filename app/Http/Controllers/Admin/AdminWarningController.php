<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWarningController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function store(Request $request, $userId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = User::findOrFail($userId);
        $admin = Auth::guard('admin')->user();

        $this->notificationService->send(
            $user->id,
            'Warning',
            $request->message,
            'warning',
            ['sent_by' => $admin->name, 'admin_id' => $admin->id]
        );

        return redirect()->route('admin.users.show', $userId)
            ->with('success', 'Warning sent to user successfully.');
    }
}
