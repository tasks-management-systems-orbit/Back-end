<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    protected $table = 'task_assignments';

    protected $fillable = [
        'task_id',
        'user_id',
        'status_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function complete(): void
    {
        if (!$this->isCompleted()) {
            $this->update(['completed_at' => now()]);

            $task = $this->task;
            if ($task && $task->auto_status) {
                $task->updateAutoStatus();
            }
        }
    }
}
