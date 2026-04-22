<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Events\TaskCompleted;


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
        'started_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'started_at' => 'datetime',
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

    public function isStarted(): bool
    {
        return !is_null($this->started_at);
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

    public function start(): void
    {
        if (!$this->isStarted()) {
            $this->update(['started_at' => now()]);
        }
    }

    public function complete(): void
    {
        if (!$this->isCompleted() && $this->canBeCompleted()) {
            $this->update(['completed_at' => now()]);
            event(new TaskCompleted($this));
        }
    }

    public function isBlocked(): bool
    {
        foreach ($this->dependencies as $dependency) {
            $type = $dependency->pivot->type;

            if ($type === 'FS' && !$dependency->isCompleted()) {
                return true;
            }

            if ($type === 'SS' && !$dependency->isStarted()) {
                return true;
            }

            if ($type === 'FF' && !$dependency->isCompleted()) {
                return true;
            }

            if ($type === 'SF' && !$dependency->isStarted()) {
                return true;
            }
        }
        return false;
    }

    public function canBeStarted(): bool
    {
        foreach ($this->dependencies as $dependency) {
            $type = $dependency->pivot->type;

            if ($type === 'FS' && !$dependency->isCompleted()) {
                return false;
            }

            if ($type === 'SS' && !$dependency->isStarted()) {
                return false;
            }
        }
        return true;
    }

    public function canBeCompleted(): bool
    {
        foreach ($this->dependencies as $dependency) {
            $type = $dependency->pivot->type;

            if ($type === 'FS' && !$dependency->isCompleted()) {
                return false;
            }

            // if ($type === 'SS' && !$dependency->isStarted()) {
            //     return false;
            // }

            if ($type === 'FF' && !$dependency->isCompleted()) {
                return false;
            }

            if ($type === 'SF' && !$dependency->isStarted()) {
                return false;
            }
        }
        return true;
    }

    public function getTypeLabel(string $type): string
    {
        return match ($type) {
            'FS' => 'Finish to Start',
            'SS' => 'Start to Start',
            'FF' => 'Finish to Finish',
            'SF' => 'Start to Finish',
            default => 'Unknown',
        };
    }

    public function getTypeDescription(string $type): string
    {
        return match ($type) {
            'FS' => 'This task cannot be started until the dependency is completed',
            'SS' => 'This task cannot be started until the dependency is started',
            'FF' => 'This task cannot be completed until the dependency is completed',
            'SF' => 'This task cannot be completed until the dependency is started',
            default => '',
        };
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

    public function getDueDateFormattedAttribute(): ?string
    {
        return $this->due_date?->format('Y-m-d');
    }

    public function getStartedAtFormattedAttribute(): ?string
    {
        return $this->started_at?->format('Y-m-d H:i:s');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    public function getIsStartedAttribute(): bool
    {
        return $this->isStarted();
    }

    public function getIsBlockedAttribute(): bool
    {
        return $this->isBlocked();
    }

    public function getCanBeStartedAttribute(): bool
    {
        return $this->canBeStarted();
    }

    public function getCanBeCompletedAttribute(): bool
    {
        return $this->canBeCompleted();
    }

    public function getAssignmentsCountAttribute(): int
    {
        return $this->assignees()->count();
    }

    public function getDependenciesCountAttribute(): int
    {
        return $this->dependencies()->count();
    }

    public function getDependentsCountAttribute(): int
    {
        return $this->dependents()->count();
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
        return $query->where(function ($q) use ($userId) {
            $q->where('assigned_to', $userId)
                ->orWhereHas('assignees', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId);
                });
        })->distinct();  
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

    public function scopeNotStarted($query)
    {
        return $query->whereNull('started_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNotNull('started_at')->whereNull('completed_at');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }
}
