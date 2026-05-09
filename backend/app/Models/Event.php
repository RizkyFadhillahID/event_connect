<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'budget',
        'category',
        'expected_participants',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function personnel()
    {
        return $this->belongsToMany(User::class, 'event_personnel', 'event_id', 'user_id')
            ->withPivot('role_in_event', 'notes')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function rundowns()
    {
        return $this->hasMany(EventRundown::class);
    }

    public function taskStats(): array
    {
        $counts = $this->tasks()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total  = $counts->sum();
        return [
            'total'      => $total,
            'pending'    => $counts->get('pending', 0),
            'in_progress' => $counts->get('in_progress', 0),
            'review'     => $counts->get('review', 0),
            'completed'  => $counts->get('completed', 0),
            'cancelled'  => $counts->get('cancelled', 0),
            'progress'   => $total > 0 ? round($counts->get('completed', 0) / $total * 100) : 0,
        ];
    }
}
