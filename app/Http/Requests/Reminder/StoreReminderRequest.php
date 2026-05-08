<?php

namespace app\Http\Requests\Reminder;

use App\Models\Reminder;
use Illuminate\Foundation\Http\FormRequest;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'nullable|string|max:1000',
            'remind_at' => 'required|date|after:now',
            'task_ids' => 'nullable|array',
            'task_ids.*' => 'exists:tasks,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $taskIds = $this->input('task_ids', []);
            $remindAt = $this->input('remind_at');

            try {
                Reminder::validateReminderDateAgainstTasks($taskIds, $remindAt);
            } catch (\Exception $e) {
                $validator->errors()->add('remind_at', $e->getMessage());
            }
        });
    }
}