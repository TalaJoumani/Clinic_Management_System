<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'location_id',
        'type',
        'appointment_time',
        'status',
        'total_price',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function medicalRecord()
    {
        return $this->hasOne(Medical_records::class);
    }
}
