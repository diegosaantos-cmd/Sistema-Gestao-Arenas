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
        'discount_amount',
        'discount_reason',
        'status',
        'pix_transaction_id',
        'receipt_url',
        'origin',
        'cash_register_entry_id',
        'paid_at',
        'refunded_at',
        'refund_amount',
        'refund_cash_register_entry_id',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
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

    /** A entrada de caixa (saída) gerada pelo reembolso, se já lançada. */
    public function refundCashRegisterEntry()
    {
        return $this->belongsTo(CashRegisterEntry::class, 'refund_cash_register_entry_id');
    }

    /** Este pagamento foi estornado (reserva paga que foi cancelada)? */
    public function foiReembolsada(): bool
    {
        return $this->refunded_at !== null;
    }
}