<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentLog extends Model
{
    protected $fillable = [
        'appointment_id',
        'action',
        'status',
        'description',
        'booking_time',
        'service_title',
        'customer_name',
        'comments',
        'staff_name',
    ];

    protected $casts = [
        'booking_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    // Action constants
    const ACTION_BOOKED = 'BOOKED';
    const ACTION_MESSAGE_SENT = 'MESSAGE';
    const ACTION_UPDATED = 'UPDATED';
    const ACTION_CHECKED_OUT = 'CHECKED_OUT';
    const ACTION_CANCELLED = 'CANCELLED';
    const ACTION_DELETED = 'DELETED';

    // Description constants for different appointment actions
    const DESC_APPOINTMENT_BOOKED = 'Appointment Booked by Customer';
    const DESC_APPOINTMENT_BOOKED_BY_STAFF = 'Appointment Booked by Staff';
    const DESC_APPOINTMENT_UPDATED = 'Appointment Updated';
    const DESC_APPOINTMENT_CANCELLED = 'Appointment Cancelled';
    const DESC_APPOINTMENT_DELETED = 'Appointment Deleted';
    const DESC_CHECKED_OUT = 'Appointment Checked Out';
    const DESC_CONFIRM_MESSAGE_SENT = 'Confirmation Message Sent';
    const DESC_REMINDER_MESSAGE_SENT = 'Reminder Message Sent';
    const DESC_APPOINTMENT_NO_SHOW = 'Appointment Marked as No-Show';

    /**
     * Get the appointment that owns the log.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
