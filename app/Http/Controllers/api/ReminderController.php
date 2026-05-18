<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reminder\StoreReminderRequest;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reminders = $request->user()
            ->reminders()
            ->orderBy('remind_at', 'asc')
            ->with('tasks')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ReminderResource::collection($reminders),
            'total' => $reminders->count(),
        ]);
    }

    public function store(StoreReminderRequest $request): JsonResponse
    {
        $reminder = Reminder::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'message' => $request->message,
            'remind_at' => $request->remind_at,
            'status' => 'pending',
        ]);

        if ($request->has('task_ids')) {
            $reminder->tasks()->sync($request->task_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reminder created successfully',
            'data' => new ReminderResource($reminder->load('tasks')),
        ], 201);
    }
}
