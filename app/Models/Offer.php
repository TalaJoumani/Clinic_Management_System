<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'title',
        'description',
        'discount_percentage',
        'is_active',
        'valid_from',
        'valid_until',
    ];
}
