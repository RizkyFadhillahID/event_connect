<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['event_id', 'user_id', 'message'];

    public function user()
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'role']);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
