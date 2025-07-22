<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = ['id'];

    // Define relationships if needed
    public function services()
    {
        return $this->hasMany(ServiceAppointment::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function logs()
    {
        return $this->hasMany(AppointmentLog::class);
    }

    public function getAmountAttribute()
    {
        return $this->services->sum('service_price');
    }
}
