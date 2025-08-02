<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherHistory extends Model
{
    /** @use HasFactory<\Database\Factories\VoucherHistoryFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'voucher_id',
        'user_id',
        'appointment_id',
        'phone',
        'name',
        'service',
        'action',
        'description',
        'pre_amount',
        'after_amount',
    ];

    protected $casts = [
        'pre_amount' => 'double',
        'after_amount' => 'double',
    ];

    /**
     * Get the voucher that owns the history.
     */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Get the user associated with the history.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the appointment associated with the history.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
