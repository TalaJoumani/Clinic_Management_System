<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'appointment_id',
        'total_amount',
        'amount_paid',
        'remaining_amount',
        'method',
        'status',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
    
}
