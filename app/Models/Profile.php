<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    protected $fillable = [
        'user_id',
        'phone',
        'bio',
        'job_title',
        'skills',
        'avatar',
        'location',
        'twitter_url',
        'alternative_email',
        'github_url',
        'portfolio_url',
        'linkedin_url',
        'cv_url',
        'language',
        'theme',
        'is_public',
        'projects_count',
        'tasks_completed',
    ];

    protected $casts = [
        'skills' => 'array',
        'is_public' => 'boolean',
        'projects_count' => 'integer',
        'tasks_completed' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'theme' => 'light',
        'is_public' => false,
        'projects_count' => 0,
        'tasks_completed' => 0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSkillsListAttribute(): array
    {
        if (is_array($this->skills)) {
            return $this->skills;
        }

        return json_decode($this->skills ?? '[]', true) ?? [];
    }

    public function setSkillsAttribute($value)
    {
        $this->attributes['skills'] = is_array($value) ? json_encode($value) : $value;
    }

    public function addSkill(string $skill): void
    {
        $skills = $this->skills_list;
        if (!in_array($skill, $skills)) {
            $skills[] = $skill;
            $this->skills = $skills;
            $this->save();
        }
    }

    public function removeSkill(string $skill): void
    {
        $skills = $this->skills_list;
        $key = array_search($skill, $skills);
        if ($key !== false) {
            unset($skills[$key]);
            $this->skills = array_values($skills);
            $this->save();
        }
    }

}
