<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DoctorSchedule;

class Doctor extends Model
{
    protected $fillable = [
        'user_id',
        'specialization',
        'day',
        'start_time',
        'end_time',
        'is_available',
        'home_visit',
        'admin_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }

        public function appointments()
        {
            return $this->hasMany(Appointment::class, 'doctor_id');
        }
    
}
