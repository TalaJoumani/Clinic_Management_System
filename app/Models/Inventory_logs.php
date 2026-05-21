<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory_logs extends Model
{
    protected $fillable = [
        'item_id',
        'type',
        'quantity_changed',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
