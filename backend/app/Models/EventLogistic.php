<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLogistic extends Model
{
    protected $fillable = [
        'event_id',
        'inventory_id',
        'user_id',
        'quantity',
        'rent_cost',
        'borrowed_at',
        'returned_at',
        'return_status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'rent_cost' => 'decimal:2',
        'borrowed_at' => 'date',
        'returned_at' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
