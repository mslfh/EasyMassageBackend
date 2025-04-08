<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Staff extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = ['profile_photo_url','email','phone'];

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path ? asset('storage/' . $this->profile_photo_path	) :
         asset('default-avatar.png');
    }

    public function getEmailAttribute()
    {
        $user = $this->user()->first();
        return  $user->email;
    }
    public function getPhoneAttribute()
    {
        $user = $this->user()->first();
        return  $user->phone;
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function bookingServices()
    {
        return $this->hasMany(
            ServiceAppointment::class,
            'staff_id',
            'id'
        );
    }

    public function appointments()
    {
        return $this->belongsToMany(
            Appointment::class,
            'service_appointments',
            'staff_id',
            'appointment_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
