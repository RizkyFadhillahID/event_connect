<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RundownLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'rundown_id',
        'user_id',
        'action',
        'old_status',
        'new_status',
        'delay_reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function rundown()
    {
        return $this->belongsTo(EventRundown::class, 'rundown_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
