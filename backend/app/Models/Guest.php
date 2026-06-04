<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory;

    protected $table = 'event_guests';

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'category',
        'rsvp_status',
        'checkin_status',
        'checked_in_at',
    ];

    protected $casts = [
        'checkin_status' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
