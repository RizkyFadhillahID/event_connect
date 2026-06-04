<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventExpense extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'category',
        'title',
        'amount',
        'vendor_name',
        'payment_method',
        'payment_status',
        'spent_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'spent_at' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
