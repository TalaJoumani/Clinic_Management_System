<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'blood_type',
        'previous_illnesses',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
