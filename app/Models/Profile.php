<?php

namespace app\Models;

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
        'alternative_email',
        'twitter_url',
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'github_url',
        'portfolio_url',
        'linkedin_url',
        'cv_url',
        'language',
        'theme',
        'is_public',
        'allow_messages',
        'allow_invitation_requests',
        'projects_count',
        'tasks_completed',
        'report_count',
    ];

    protected $casts = [
        'skills' => 'array',
        'is_public' => 'boolean',
        'allow_messages' => 'boolean',
        'allow_invitation_requests' => 'boolean',
        'projects_count' => 'integer',
        'tasks_completed' => 'integer',
        'report_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'language' => 'ar',
        'theme' => 'light',
        'is_public' => false,
        'allow_messages' => false,
        'allow_invitation_requests' => false,
        'projects_count' => 0,
        'tasks_completed' => 0,
        'report_count' => 0,
        'skills' => '[]',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSkillsAttribute($value): array
    {
        $skills = json_decode($value ?? '[]', true);

        if (!is_array($skills)) {
            return [];
        }

        if (isset($skills[0]) && is_string($skills[0])) {
            $converted = [];
            foreach ($skills as $skill) {
                $converted[] = ['name' => $skill, 'rating' => 5];
            }
            return $converted;
        }

        return $skills;
    }

    public function setSkillsAttribute($value): void
    {
        if (is_string($value)) {
            $this->attributes['skills'] = $value;
            return;
        }

        if (!is_array($value)) {
            $this->attributes['skills'] = '[]';
            return;
        }

        $validated = [];
        foreach ($value as $skill) {
            if (is_array($skill) && isset($skill['name'])) {
                $validated[] = [
                    'name' => trim($skill['name']),
                    'rating' => isset($skill['rating']) ? min(10, max(1, (int)$skill['rating'])) : 5
                ];
            } elseif (is_string($skill)) {
                // Handle old format
                $validated[] = ['name' => $skill, 'rating' => 5];
            }
        }

        $this->attributes['skills'] = json_encode($validated);
    }

    public function getSkillNamesAttribute(): array
    {
        return array_column($this->skills, 'name');
    }

    public function addSkill(string $skillName, ?int $rating = null): bool
    {
        $rating = $rating ?? 5;
        $skills = $this->skills;
        $skillName = trim($skillName);

        foreach ($skills as $index => $skill) {
            if (strcasecmp($skill['name'], $skillName) === 0) {
                return false;
            }
        }

        $skills[] = [
            'name' => $skillName,
            'rating' => min(10, max(1, $rating))
        ];

        $this->skills = $skills;
        return (bool) $this->save();
    }

    public function updateSkillRating(string $skillName, int $rating): bool
    {
        $skills = $this->skills;
        $skillName = trim($skillName);
        $rating = min(10, max(1, $rating));

        foreach ($skills as $index => $skill) {
            if (strcasecmp($skill['name'], $skillName) === 0) {
                $skills[$index]['rating'] = $rating;
                $this->skills = $skills;
                return (bool) $this->save();
            }
        }

        return false;
    }

    public function removeSkill(string $skillName): bool
    {
        $skills = $this->skills;
        $skillName = trim($skillName);

        foreach ($skills as $index => $skill) {
            if (strcasecmp($skill['name'], $skillName) === 0) {
                unset($skills[$index]);
                $this->skills = array_values($skills);
                return (bool) $this->save();
            }
        }

        return false;
    }

    public function getAverageSkillRatingAttribute(): float
    {
        $skills = $this->skills;

        if (empty($skills)) {
            return 0;
        }

        $total = array_sum(array_column($skills, 'rating'));
        return round($total / count($skills), 1);
    }

    public function getTopSkillsAttribute(?int $limit = null): array
    {
        $limit = $limit ?? 5;
        $skills = $this->skills;

        usort($skills, function ($a, $b) {
            return $b['rating'] <=> $a['rating'];
        });

        return array_slice($skills, 0, $limit);
    }

    protected static function booted()
    {
        static::saving(function ($profile) {
            if (!$profile->is_public) {
                $profile->allow_messages = false;
                $profile->allow_invitation_requests = false;
            }
        });
    }
}
