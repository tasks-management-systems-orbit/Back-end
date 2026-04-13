<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use SoftDeletes;

    protected $table = 'tasks';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status_id',
        'priority',
        'due_date',
        'position',
        'created_by',
        'assigned_to',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'priority' => 'medium',
        'position' => 0,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withPivot('type')
            ->withTimestamps();
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withPivot('type')
            ->withTimestamps();
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function isOverdue(): bool
    {
        if ($this->isCompleted() || is_null($this->due_date)) {
            return false;
        }
        return $this->due_date->isPast();
    }

    public function isBlocked(): bool
    {
        foreach ($this->dependencies as $dependency) {
            if (!$dependency->isCompleted()) {
                return true;
            }
        }
        return false;
    }

    public function canBeCompleted(): bool
    {
        foreach ($this->dependencies as $dependency) {
            if (!$dependency->isCompleted()) {
                return false;
            }
        }
        return true;
    }

    public function complete(): void
    {
        $this->update([
            'completed_at' => now(),
        ]);
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'Urgent',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            default => 'Medium',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => '#EF4444',
            'high' => '#F97316',
            'medium' => '#F59E0B',
            'low' => '#10B981',
            default => '#6B7280',
        };
    }

    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByStatus($query, int $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function scopeByAssignee($query, int $userId)
    {
        return $query->where('assigned_to', $userId)
            ->orWhereHas('assignees', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function getDueDateFormattedAttribute(): ?string
    {
        return $this->due_date?->format('Y-m-d');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    public function getIsBlockedAttribute(): bool
    {
        return $this->isBlocked();
    }

    public function getCanBeCompletedAttribute(): bool
    {
        return $this->canBeCompleted();
    }

    public function getAssignmentsCountAttribute(): int
    {
        return $this->assignments()->count();
    }

    public function getDependenciesCountAttribute(): int
    {
        return $this->dependencies()->count();
    }

    public function getDependentsCountAttribute(): int
    {
        return $this->dependents()->count();
    }
}
