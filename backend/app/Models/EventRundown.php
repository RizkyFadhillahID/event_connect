<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRundown extends Model
{
    protected $fillable = [
        'event_id',
        'event_date',
        'title',
        'description',
        'category',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'pic_id',
        'location_note',
        'notes',
        'order_number',
        'started_at',
        'ended_at',
        'delay_minutes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'started_at' => 'datetime',
            'ended_at'   => 'datetime',
        ];
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Tasks that must be completed before this rundown item can go live */
    public function dependencyTasks()
    {
        return $this->belongsToMany(Task::class, 'rundown_dependencies', 'rundown_id', 'task_id')
            ->withTimestamps();
    }

    public function logs()
    {
        return $this->hasMany(RundownLog::class, 'rundown_id')->latest('created_at');
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('event_date', $date);
    }

    // ──────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────

    /**
     * Returns true when at least one dependency task is not completed.
     * Used as a visual warning only – not a system blocker.
     */
    public function getHasUnfinishedDependenciesAttribute(): bool
    {
        return $this->dependencyTasks()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();
    }
}
