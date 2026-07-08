<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'court_id', 'client_id', 'employee_id', 'date',
        'start_time', 'end_time', 'total_amount', 'payment_method_id', 'status', 'notes',
        'cancelled_by', 'cancellation_reason', 'cancelled_at', 'cancellation_fee_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'cancelled_at' => 'datetime',
        'cancellation_fee_amount' => 'decimal:2',
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function courtWithTrashed()
    {
        return $this->belongsTo(Court::class, 'court_id')->withTrashed();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Número da reserva na sequência do CLIENTE dono dela (1, 2, 3...), na
     * ordem em que ele reservou. Evita expor o id global do banco, que
     * "pularia" (ex.: a 2ª reserva do cliente aparecer como #10).
     */
    public function numeroDoCliente(): int
    {
        return static::where('client_id', $this->client_id)
            ->where('id', '<=', $this->id)
            ->count();
    }

    /**
     * Número da reserva na sequência da ARENA (1, 2, 3...), na ordem de
     * criação dentro daquela arena. Conta reservas de quadras já excluídas
     * também, para a sequência não pular.
     */
    public function numeroNaArena(): int
    {
        $arenaId = $this->court?->arena_id ?? $this->courtWithTrashed?->arena_id;

        if (! $arenaId) {
            return $this->id;
        }

        return static::whereIn('court_id', static::courtIdsDaArena($arenaId))
            ->where('id', '<=', $this->id)
            ->count();
    }

    /**
     * Mapa [booking_id => número na arena] para todas as reservas de uma
     * arena — usado nas listagens para evitar uma consulta por linha.
     */
    public static function numerosNaArena(int $arenaId): array
    {
        return static::whereIn('court_id', static::courtIdsDaArena($arenaId))
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->flip()
            ->map(fn ($pos) => $pos + 1)
            ->all();
    }

    /**
     * Ids das quadras (inclusive excluídas) de uma arena.
     */
    protected static function courtIdsDaArena(int $arenaId)
    {
        return Court::withTrashed()->where('arena_id', $arenaId)->pluck('id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * O cliente pode pagar online agora? (confirmada, não paga e forma PIX/cartão).
     * Dinheiro é pago na arena, então não entra no fluxo online.
     */
    public function podePagarOnline(): bool
    {
        return $this->status === 'confirmed'
            && ! $this->isPaga()
            && in_array($this->paymentMethod?->type, ['pix', 'card']);
    }

    /**
     * Confirmada, não paga e a forma é dinheiro (paga na arena ao usar).
     */
    public function pagaNaArena(): bool
    {
        return $this->status === 'confirmed'
            && ! $this->isPaga()
            && $this->paymentMethod?->type === 'cash';
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
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
     * A reserva está "em andamento": confirmada e o horário atual está entre o
     * início e o fim (já começou mas ainda não terminou).
     */
    public function estaEmAndamento(): bool
    {
        if ($this->status !== 'confirmed') {
            return false;
        }

        $inicio = Carbon::parse($this->date->toDateString() . ' ' . $this->start_time);
        $fim = Carbon::parse($this->date->toDateString() . ' ' . $this->end_time);

        return now()->greaterThanOrEqualTo($inicio) && now()->lessThan($fim);
    }

    /**
     * O cliente pode editar somente enquanto o cancelamento ainda seria GRÁTIS
     * — ou seja, usando a MESMA janela que a arena configurou para a taxa de
     * cancelamento. Dentro da janela de taxa (ou já iniciada), a edição fica
     * bloqueada. Se a arena não cobra taxa, pode editar até o início.
     */
    public function podeSerEditadaPeloCliente(): bool
    {
        return $this->regraCancelamentoCliente() === 'livre';
    }

    /**
     * Texto da taxa numa reserva CANCELADA (para o histórico):
     * "Com taxa de R$ X" ou "Sem taxa". null se não estiver cancelada.
     */
    public function taxaCancelamentoDescricao(): ?string
    {
        if ($this->status !== 'cancelled') {
            return null;
        }

        $valor = (float) $this->cancellation_fee_amount;

        return $valor > 0
            ? 'Com taxa de R$ ' . number_format($valor, 2, ',', '.')
            : 'Sem taxa';
    }

    /**
     * Valor da taxa de cancelamento desta reserva, conforme a config da arena
     * (fixo em R$ ou percentual sobre o valor da reserva). 0 se a arena não cobra.
     */
    public function valorTaxaCancelamento(): float
    {
        $arena = $this->court?->arena;

        return $arena ? $arena->taxaCancelamentoPara((float) $this->total_amount) : 0.0;
    }

    /**
     * Regra de cancelamento pelo CLIENTE, usando a config da arena:
     * - pendente: pode cancelar sempre, sem taxa;
     * - confirmada: depende da arena —
     *     • não cobra taxa -> 'livre';
     *     • cobra no modo 'sempre' -> 'taxa';
     *     • cobra no modo 'janela' -> 'livre' se faltar mais de X horas, senão 'taxa';
     * - já começou/passada/cancelada/concluída: não pode (null).
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

        if ($this->status !== 'confirmed') {
            return null;
        }

        $arena = $this->court?->arena;

        // Arena não cobra taxa (ou valor zerado) -> sempre livre.
        if (! $arena || ! $arena->charges_cancellation_fee || $arena->taxaCancelamentoPara((float) $this->total_amount) <= 0) {
            return 'livre';
        }

        // Cobra sempre que estiver confirmada.
        if ($arena->cancellation_fee_mode === 'always') {
            return 'taxa';
        }

        // Modo janela: grátis se faltar mais de X horas para o início.
        $horas = (int) ($arena->cancellation_fee_window_hours ?? 0);
        $limite = $inicio->copy()->subHours($horas);

        return now()->lessThan($limite) ? 'livre' : 'taxa';
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
                $booking->notificarClienteConfirmada();
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

                if (! $booking->isPaga()) {
                    $booking->notificarClienteNaoPaga();
                }
            }
        }
    }

    /**
     * Descrição curta da reserva para mensagens/notificações.
     */
    public function descricaoCurta(): string
    {
        return ($this->court->name ?? 'Quadra') . ' — '
            . $this->date->format('d/m/Y') . ' '
            . substr($this->start_time, 0, 5) . '–' . substr($this->end_time, 0, 5);
    }

    public function notificarClienteConfirmada(?int $sentBy = null): void
    {
        UserNotification::paraReserva(
            $this,
            'Reserva confirmada',
            'Sua reserva foi confirmada: ' . $this->descricaoCurta() . '.',
            $sentBy
        );
    }

    public function notificarClienteCancelada(?string $motivo = null, ?int $sentBy = null): void
    {
        $texto = 'Sua reserva foi cancelada pela arena: ' . $this->descricaoCurta() . '.';
        if ($motivo) {
            $texto .= ' Motivo: ' . $motivo;
        }

        UserNotification::paraReserva($this, 'Reserva cancelada', $texto, $sentBy);
    }

    public function notificarClienteReagendada(?int $sentBy = null): void
    {
        UserNotification::paraReserva(
            $this,
            'Reserva reagendada',
            'Sua reserva foi reagendada pela arena para: ' . $this->descricaoCurta() . '.',
            $sentBy
        );
    }

    public function notificarClienteNaoPaga(?int $sentBy = null): void
    {
        UserNotification::paraReserva(
            $this,
            'Reserva não paga',
            'A reserva ' . $this->descricaoCurta() . ' foi realizada e está sem pagamento. '
                . 'Você ainda pode pagá-la em "Meus Agendamentos".',
            $sentBy
        );
    }
}
