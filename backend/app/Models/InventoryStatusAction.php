<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganization;

class InventoryStatusAction extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'inventory_id',
        'user_id',
        'type',
        'quantity',
        'status',
        'notes',
        'resolved_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
