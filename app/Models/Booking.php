<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $casts = [
        'date' => 'date',
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
