<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectReport extends Model
{
    use HasFactory;

    protected $table = 'project_reports';

    protected $fillable = [
        'reporter_id',
        'reported_project_id',
        'reason',
        'details',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'reported_project_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }
}
