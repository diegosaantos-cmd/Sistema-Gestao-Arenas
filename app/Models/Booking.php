<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'court_id', 'client_id', 'employee_id', 'date',
        'start_time', 'end_time', 'total_amount', 'status', 'notes',
        'cancelled_by', 'cancellation_reason', 'cancelled_at',
    ];

    protected $casts = [
        'date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * A reserva já tem um pagamento confirmado (status 'paid')?
     * Usa a relação já carregada quando disponível (evita N+1 nas listagens).
     */
    public function isPaga(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->contains(fn ($p) => $p->status === 'paid');
        }

        return $this->payments()->where('status', 'paid')->exists();
    }

    /**
     * Situação de pagamento da reserva:
     * - 'pago'     : já tem pagamento confirmado;
     * - 'atrasado' : não pago e o horário já terminou;
     * - 'a_pagar'  : não pago e ainda vai acontecer;
     * - null       : não se aplica (pendente ou cancelada — ainda não é uma
     *                reserva que vai acontecer).
     */
    public function situacaoPagamento(): ?string
    {
        // Só confirmadas/realizadas têm situação de pagamento.
        if (! in_array($this->status, ['confirmed', 'completed'])) {
            return null;
        }

        if ($this->isPaga()) {
            return 'pago';
        }

        $fim = Carbon::parse($this->date->toDateString() . ' ' . $this->end_time);

        return now()->greaterThan($fim) ? 'atrasado' : 'a_pagar';
    }

    /**
     * O cliente pode editar somente até uma hora antes do início.
     */
    public function podeSerEditadaPeloCliente(): bool
    {
        if (! in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        $inicio = Carbon::parse($this->date->toDateString() . ' ' . $this->start_time);

        return now()->lessThanOrEqualTo($inicio->copy()->subHour());
    }

    /**
     * Usa a taxa definida na arena/sistema e adota 30% como padrão.
     */
    public function percentualTaxaCancelamento(): float
    {
        $arena = $this->court?->arena;

        foreach (['cancellation_fee_percentage', 'cancellation_fee_percent', 'cancel_fee_percent'] as $campo) {
            $percentual = $arena?->getAttribute($campo);

            if (is_numeric($percentual)) {
                return max(0, (float) $percentual);
            }
        }

        return max(0, (float) config('bookings.cancellation_fee_percent', 30));
    }

    public function valorTaxaCancelamento(): float
    {
        return round((float) $this->total_amount * $this->percentualTaxaCancelamento() / 100, 2);
    }

    /**
     * Regra de cancelamento pelo CLIENTE:
     * - pendente: pode cancelar sempre, sem taxa;
     * - confirmada: grátis até 1h antes do início, com taxa se faltar 1h ou menos;
     * - já começou/passada/cancelada/concluída: não pode.
     *
     * Retorna 'livre' | 'taxa' | null (null = não pode cancelar).
     */
    public function regraCancelamentoCliente(): ?string
    {
        $inicio = Carbon::parse($this->date->toDateString() . ' ' . $this->start_time);

        if (now()->greaterThanOrEqualTo($inicio)) {
            return null;
        }

        if ($this->status === 'pending') {
            return 'livre';
        }

        if ($this->status === 'confirmed') {
            return now()->lt($inicio->copy()->subHour()) ? 'livre' : 'taxa';
        }

        return null;
    }

    /**
     * Prazo que o dono/atendente tem para confirmar/cancelar a reserva.
     * - criada com mais de 10 min até o início: 10 min;
     * - criada com 10 min ou menos: metade do tempo que faltava.
     */
    public function prazoConfirmacao(): Carbon
    {
        $inicio = Carbon::parse($this->date->toDateString() . ' ' . $this->start_time);
        $criado = $this->created_at ?? now();
        $minsAteInicio = $criado->diffInMinutes($inicio, false); // negativo se já passou

        if ($minsAteInicio <= 10) {
            return $criado->copy()->addSeconds(max(0, (int) ($minsAteInicio * 60 / 2)));
        }

        return $criado->copy()->addMinutes(10);
    }

    /**
     * Está pendente e o prazo de confirmação já passou.
     */
    public function deveAutoConfirmar(): bool
    {
        return $this->status === 'pending'
            && now()->greaterThanOrEqualTo($this->prazoConfirmacao());
    }

    /**
     * Confirma automaticamente as reservas pendentes cujo prazo expirou.
     * Chamada de forma "preguiçosa" ao abrir as telas de agendamentos.
     */
    public static function autoConfirmarExpiradas(?array $courtIds = null): void
    {
        $query = static::where('status', 'pending');

        if ($courtIds !== null) {
            $query->whereIn('court_id', $courtIds);
        }

        foreach ($query->get() as $booking) {
            if ($booking->deveAutoConfirmar()) {
                $booking->update(['status' => 'confirmed']);
            }
        }
    }

    /**
     * Marca como realizadas (completed) as reservas confirmadas cujo horário
     * de término já passou. Chamada de forma "preguiçosa" ao abrir as telas.
     */
    public static function autoCompletarRealizadas(?array $courtIds = null): void
    {
        $query = static::where('status', 'confirmed');

        if ($courtIds !== null) {
            $query->whereIn('court_id', $courtIds);
        }

        foreach ($query->get() as $booking) {
            $fim = Carbon::parse($booking->date->toDateString() . ' ' . $booking->end_time);

            if (now()->greaterThan($fim)) {
                $booking->update(['status' => 'completed']);
            }
        }
    }
}
