<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'quantity',
        'min_quantity',
        'category',
    ];

        public function inventoryLogs()
        {
            return $this->hasMany(Inventory_Logs::class);
        }
}
