<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'event_id',
        'assigned_to',
        'created_by',
        'parent_task_id',
        'priority',
        'status',
        'due_date',
        'due_time',
        'category',
        'notes',
        'order',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
        ];
    }

    // Linked event
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // User this task is assigned to
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // User who created this task
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Sub-tasks (hierarchy)
    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id')->with(['assignee', 'subtasks']);
    }

    // Parent task
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    // Comments thread
    public function comments()
    {
        return $this->hasMany(TaskComment::class)->with('user')->latest();
    }

    // Scope: filter by event
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    // Scope: filter by assignee
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Scope: only root tasks (no parent)
    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_task_id');
    }

    // Derived: is overdue
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, ['completed', 'cancelled']);
    }
}
