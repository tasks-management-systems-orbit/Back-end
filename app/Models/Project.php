<?php

namespace app\Models;

use App\Models\ProjectComment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Builder;


class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'name',
        'description',
        'image',
        'status',
        'visibility',
        'start_date',
        'end_date',
        'created_by',
        'allow_join_requests',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'allow_join_requests' => 'boolean',
    ];

    protected $appends = [
        'users_count',
        'completed_tasks_count',
    ];

    // ============== Relationships ==============

    public function joinRequests()
    {
        return $this->hasMany(Request::class)->where('type', 'join_request');
    }

    public function invitations()
    {
        return $this->hasMany(Request::class)->where('type', 'invitation');
    }

    public function sentJoinRequests()
    {
        return $this->hasMany(Request::class, 'project_id')->where('type', 'join_request');
    }

    public function projectComments(): HasMany
    {
        return $this->hasMany(ProjectComment::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorite_projects', 'project_id', 'user_id')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function taskStatuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class);
    }


    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Comment::class,
            Task::class,
            'project_id',
            'task_id',
            'id',
            'id'
        );
    }
    public function projectReports()
    {
        return $this->hasMany(ProjectReport::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }


    // ============== Helper Methods ==============

    public function isOwner(int $userId): bool
    {
        return $this->created_by === $userId;
    }

    public function hasUser(int $userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    public function getUserRole(int $userId): ?string
    {
        $user = $this->users()->where('user_id', $userId)->first();
        return $user?->pivot->role;
    }

    public function isManager(int $userId): bool
    {
        $role = $this->getUserRole($userId);
        return $role === 'owner' || $role === 'manager';
    }

    public function getUsersCountAttribute(): int
    {
        return $this->users_count ?? $this->users()->count();
    }

    public function getCompletedTasksCountAttribute(): int
    {
        return $this->tasks()->whereNotNull('completed_at')->count();
    }

    // ============== Management Methods ==============

    public function addUser(int $userId, string $role = 'user'): bool
    {
        if ($this->hasUser($userId)) {
            return false;
        }

        $this->users()->attach($userId, ['role' => $role]);
        return true;
    }

    public function removeUser(int $userId): bool
    {
        if (!$this->hasUser($userId)) {
            return false;
        }

        $this->users()->detach($userId);
        return true;
    }

    public function updateUserRole(int $userId, string $role): bool
    {
        if (!$this->hasUser($userId)) {
            return false;
        }

        $this->users()->updateExistingPivot($userId, ['role' => $role]);
        return true;
    }

    public function getManagers(): \Illuminate\Support\Collection
    {
        return $this->users()
            ->wherePivotIn('role', ['owner', 'manager'])
            ->get();
    }

    // ============== Query Scopes ==============

    public function scopeActive(Builder $query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeForUser(Builder $query, int $userId)
    {
        return $query->where('created_by', $userId)
            ->orWhereHas('users', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }
}
