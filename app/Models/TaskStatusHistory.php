<?php

namespace App\Models;

use app\Models\Task;
use app\Models\TaskStatus;
use app\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskStatusHistory extends Model
{
    protected $fillable = [
        'task_id', 'from_status_id', 'to_status_id', 'changed_by', 'changed_at'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'to_status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
