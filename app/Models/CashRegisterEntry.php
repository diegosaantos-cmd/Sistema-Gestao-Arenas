<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterEntry extends Model
{
    protected $table = 'cash_register_entries';
    // A tabela só tem created_at (sem updated_at).
    public $timestamps = false;

    protected $fillable = [
        'cash_register_id',
        'booking_id',
        'type',
        'amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}