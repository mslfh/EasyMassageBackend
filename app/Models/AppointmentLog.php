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
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    // Description constants for different appointment actions
    const DESC_APPOINTMENT_BOOKED = 'appointment booked by customer';
    const DESC_APPOINTMENT_BOOKED_BY_STAFF = 'appointment booked by staff';
    const DESC_APPOINTMENT_UPDATED = 'appointment updated';
    const DESC_APPOINTMENT_CANCELLED = 'appointment cancelled';
    const DESC_APPOINTMENT_DELETED = 'appointment deleted';
    const DESC_CHECKED_OUT = 'checked out';
    const DESC_CONFIRM_MESSAGE_SENT = 'confirmation message sent';
    const DESC_REMINDER_MESSAGE_SENT = 'reminder message sent';

    const DESC_APPOINTMENT_NO_SHOW = 'appointment marked as no-show';
    const DESC_SERVICE_STARTED = 'service started';
    const DESC_SERVICE_COMPLETED = 'service completed';

    /**
     * Get the appointment that owns the log.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
