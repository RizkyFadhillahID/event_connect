<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventBudgetAllocation extends Model
{
    protected $fillable = ['event_id', 'category', 'allocated_amount', 'notes'];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
