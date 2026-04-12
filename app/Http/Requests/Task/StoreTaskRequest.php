<?php


namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date|after_or_equal:today',
            'status_id' => 'nullable|exists:task_statuses,id',
            'assigned_to' => 'nullable|exists:users,id',
            'assignees' => 'nullable|array',
            'assignees.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required',
            'priority.in' => 'Priority must be low, medium, high, or urgent',
            'due_date.after_or_equal' => 'Due date cannot be in the past',
            'status_id.exists' => 'Selected status does not exist',
            'assigned_to.exists' => 'Selected user does not exist',
        ];
    }
}
