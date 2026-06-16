<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Mail\OtpMail;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
            'first_name',
            'last_name' ,
            'email'  ,
            'password' ,
            'role'    ,
            'birth',
            'phone',
            'gender',
            'is_verified',
            'fcm_token',
          
    ];

    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'user_id');
    }

    public function addedDoctors()
    {
        return $this->hasMany(Doctor::class, 'admin_id');
    }
    
    public function patient()
    {
        return $this->hasOne(Patient::class, 'user_id');
    }

    public function otp()
    {
        return $this->hasOne(Otp::class, 'user_id');
    }

    public function location()
    {
        return $this->hasOne(Location::class, 'user_id' );
    }

    public function medicalRecord()
    {
        return $this->hasMany(Medical_records::class, 'patient_id');
    }

    public function consultation()
    {
        return $this->hasMany(Consultation::class, 'patient_id');
    }

    public function doctorConsultation()
    {
        return $this->hasMany(Consultation::class, 'doctor_id');
    }

    public function chat()
    {
        return $this->hasMany(Chat::class, 'sender_id');
    }
    

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
