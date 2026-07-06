<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    // A tabela só tem created_at (sem updated_at).
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'payment_method_id',
        'amount',
        'status',
        'pix_transaction_id',
        'receipt_url',
        'origin',
        'cash_register_entry_id',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cashRegisterEntry()
    {
        return $this->belongsTo(CashRegisterEntry::class);
    }
}