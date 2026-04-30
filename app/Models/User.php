<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_active',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function ($user) {
            $user->profile()->create([
                'user_id' => $user->id,
                'language' => 'ar',
                'theme' => 'light',
                'is_public' => false,
                'allow_messages' => false,
                'allow_invitation_requests' => false,
                'projects_count' => 0,
                'tasks_completed' => 0,
                'report_count' => 0,
            ]);
            $user->note()->create([
                'title' => 'My Note',
                'content' => null,
                'color' => '#ffffff',
            ]);
        });
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(): void
    {
        $this->update(['email_verified_at' => now()]);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function blockedUsers()
    {
        return $this->hasMany(BlockedUser::class, 'user_id');
    }

    public function blockUser(User $user, ?string $reason = null): bool
    {
        if ($this->id === $user->id) {
            return false;
        }

        return (bool) BlockedUser::updateOrCreate([
            'user_id' => $this->id,
            'blocked_user_id' => $user->id,
        ], [
            'reason' => $reason,
        ]);
    }

    public function unblockUser(User $user): bool
    {
        return (bool) BlockedUser::where('user_id', $this->id)
            ->where('blocked_user_id', $user->id)
            ->delete();
    }

    public function isBlocking(User $user): bool
    {
        return BlockedUser::where('user_id', $this->id)
            ->where('blocked_user_id', $user->id)
            ->exists();
    }

    public function isBlockedBy(User $user): bool
    {
        return BlockedUser::where('user_id', $user->id)
            ->where('blocked_user_id', $this->id)
            ->exists();
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_assignments');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function sentRequests()
    {
        return $this->hasMany(Request::class, 'sender_id');
    }

    public function receivedRequests()
    {
        return $this->hasMany(Request::class, 'receiver_id');
    }

    public function reportsMade()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function note()
    {
        return $this->hasOne(Note::class);
    }

    public function projectReports()
    {
        return $this->hasMany(ProjectReport::class, 'reporter_id');
    }

    public function projectComments()
    {
        return $this->hasMany(ProjectComment::class, 'user_id');
    }

    public function hasReported(User $user): bool
    {
        return Report::where('reporter_id', $this->id)
            ->where('reported_user_id', $user->id)
            ->exists();
    }

    public function getReportsCountAttribute(): int
    {
        return Report::where('reported_user_id', $this->id)->count();
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'user_id');
    }

    public function favoriteUsers()
    {
        return $this->belongsToMany(User::class, 'favorites', 'user_id', 'favorite_user_id')
            ->withTimestamps();
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites', 'favorite_user_id', 'user_id')
            ->withTimestamps();
    }

    public function addToFavorites(User $user): bool
    {
        if ($this->id === $user->id) {
            return false;
        }

        if ($this->favoriteUsers()->where('favorite_user_id', $user->id)->exists()) {
            return false;
        }

        $this->favoriteUsers()->attach($user->id);
        return true;
    }

    public function removeFromFavorites(User $user): bool
    {
        return $this->favoriteUsers()->detach($user->id) > 0;
    }

    public function isFavorite(User $user): bool
    {
        return $this->favoriteUsers()->where('favorite_user_id', $user->id)->exists();
    }

    public function favoriteProjects()
    {
        return $this->belongsToMany(Project::class, 'favorite_projects', 'user_id', 'project_id')
            ->withTimestamps();
    }

    public function addProjectToFavorites(Project $project): bool
    {
        if ($this->favoriteProjects()->where('project_id', $project->id)->exists()) {
            return false;
        }

        $this->favoriteProjects()->attach($project->id);
        return true;
    }

    public function removeProjectFromFavorites(Project $project): bool
    {
        return $this->favoriteProjects()->detach($project->id) > 0;
    }

    public function isProjectFavorite(Project $project): bool
    {
        return $this->favoriteProjects()->where('project_id', $project->id)->exists();
    }
}
