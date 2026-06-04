<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'evaluation_notes',
        'recommendations',
        'budget_snapshot',
        'task_snapshot',
        'logistic_snapshot',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'budget_snapshot' => 'array',
            'task_snapshot' => 'array',
            'logistic_snapshot' => 'array',
            'finalized_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
