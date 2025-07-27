<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentLog extends Model
{
    protected $fillable = [
        'appointment_id',
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

    // Description constants for different appointment actions
    const DESC_APPOINTMENT_BOOKED = 'Appointment booked by customer';
    const DESC_APPOINTMENT_BOOKED_BY_STAFF = 'Appointment booked by staff';
    const DESC_APPOINTMENT_UPDATED = 'Appointment updated';
    const DESC_APPOINTMENT_CANCELLED = 'Appointment cancelled';
    const DESC_APPOINTMENT_DELETED = 'Appointment deleted';
    const DESC_CHECKED_OUT = 'Appointment checked out';
    const DESC_CONFIRM_MESSAGE_SENT = 'Confirmation message sent';
    const DESC_REMINDER_MESSAGE_SENT = 'Reminder message sent';
    const DESC_APPOINTMENT_NO_SHOW = 'Appointment marked as no-show';

    /**
     * Get the appointment that owns the log.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
