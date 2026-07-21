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
        // Texto com que este pagamento aparece no caixa. Gravado na criação
        // porque só o pagamento sabe se é reserva ou taxa de cancelamento —
        // quem lança depois não teria como deduzir.
        'description',
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

    /** Foi pago em DINHEIRO? (não tem estorno automático — devolução física.) */
    public function ehDinheiro(): bool
    {
        return $this->paymentMethod?->type === 'cash';
    }

    /**
     * Como o reembolso chega ao cliente, conforme a forma de pagamento. Deixa claro
     * que pix/cartão são estornados pela própria transação — não dependem de o
     * cliente ter cadastro nem de informar dados bancários (funciona online ou na
     * arena/maquininha). Só o dinheiro exige devolução física.
     */
    public function comoReembolsar(): string
    {
        return match ($this->paymentMethod?->type) {
            'cash' => 'Pago em dinheiro — não há estorno automático; a devolução é feita '
                . 'em dinheiro, na arena.',
            'pix'  => 'Pago via PIX — o estorno volta pela própria transação, para a conta '
                . 'que pagou.',
            'card' => 'Pago no cartão — o estorno é feito no mesmo cartão do pagamento.',
            default => 'Estorno por ' . ($this->paymentMethod?->label ?? 'mesmo meio do pagamento') . '.',
        };
    }
}