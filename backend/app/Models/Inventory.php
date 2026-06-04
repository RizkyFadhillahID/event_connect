<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use \App\Traits\BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'item_name',
        'serial_number',
        'total_quantity',
        'available_quantity',
        'ownership',
        'default_rent_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'available_quantity' => 'integer',
        'default_rent_price' => 'decimal:2',
    ];

    public function logistics()
    {
        return $this->hasMany(EventLogistic::class);
    }
}
