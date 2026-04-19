<?php

namespace App\Models;

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
}
