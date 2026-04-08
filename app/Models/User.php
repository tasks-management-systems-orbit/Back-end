<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        'password' => 'hashed',
    ];

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
}
