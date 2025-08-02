<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    /** @use HasFactory<\Database\Factories\VoucherFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the voucher histories for the voucher.
     */
    public function histories()
    {
        return $this->hasMany(VoucherHistory::class);
    }
}
