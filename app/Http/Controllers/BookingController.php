<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\CourtScheduleService;
use App\Services\PaymentService;
use App\Support\ArenaAtual;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Todos os próximos agendamentos da arena atual (de hoje em diante,
     * ignorando cancelados) — tela própria pra não alongar o dashboard.
     */
    public function index()
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        $idsQuadras = $arena->courts()->pluck('id')->all();
        Booking::autoConfirmarExpiradas($idsQuadras);
        Booking::autoCompletarRealizadas($idsQuadras);

        $campo = request('campo', 'cliente');
        $q = trim((string) request('q'));

        // Só confirmados. Os pendentes ficam na tela "Aguardando confirmação".
        $query = Booking::with(['court', 'client.user', 'payments'])
            ->whereIn('court_id', $arena->courts()->select('id'))
            ->whereDate('date', '>=', now()->toDateString())
            ->where('status', 'confirmed');

        if ($q !== '') {
            $query->where(function ($qb) use ($campo, $q) {
                if ($campo === 'quadra') {
                    $qb->whereHas('court', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                } elseif ($campo === 'data') {
                    // Data exata escolhida no calendário (aaaa-mm-dd).
                    $qb->whereDate('date', $q);
                } else {
                    // A reserva presencial não tem cliente cadastrado: o nome de quem
                    // vai jogar fica em guest_name. Sem este orWhere, ela nunca
                    // apareceria numa busca por nome.
                    $qb->whereHas('client.user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                        ->orWhere('guest_name', 'like', "%{$q}%");
                }
            });
        }

        $bookings = $query->orderBy('date')->orderBy('start_time')->get();

        return view('bookings.index', compact('arena', 'bookings'));
    }

    /**
     * Histórico da arena atual: reservas já passadas (data < hoje) ou
     * canceladas/concluídas. Mesma busca da listagem, sem ações.
     */
    public function history()
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        $campo = request('campo', 'cliente');
        $q = trim((string) request('q'));

        $query = Booking::with(['court', 'client.user', 'payments'])
            ->whereIn('court_id', $arena->courts()->select('id'))
            ->where(function ($w) {
                $w->whereDate('date', '<', now()->toDateString())
                    ->orWhereIn('status', ['cancelled', 'completed']);
            });

        // Filtro por origem: online (site) ou registrada no balcão (presencial).
        $origem = request('origem');
        if (in_array($origem, [Booking::ORIGEM_SITE, Booking::ORIGEM_PRESENCIAL], true)) {
            $query->where('origin', $origem);
        }

        if ($q !== '') {
            $query->where(function ($qb) use ($campo, $q) {
                if ($campo === 'quadra') {
                    $qb->whereHas('court', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                } elseif ($campo === 'data') {
                    $qb->whereDate('date', $q);
                } else {
                    // A reserva presencial não tem cliente cadastrado: o nome de quem
                    // vai jogar fica em guest_name. Sem este orWhere, ela nunca
                    // apareceria numa busca por nome.
                    $qb->whereHas('client.user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                        ->orWhere('guest_name', 'like', "%{$q}%");
                }
            });
        }

        $bookings = $query->orderBy('date', 'desc')->orderBy('start_time', 'desc')->get();

        // Filtro por situação: concluídas / canceladas / pagas / atrasadas.
        $situacao = request('situacao');
        $bookings = match ($situacao) {
            'concluidas' => $bookings->where('status', 'completed')->values(),
            'canceladas' => $bookings->where('status', 'cancelled')->values(),
            'pagas'      => $bookings->filter(fn ($b) => $b->isPaga())->values(),
            'atrasadas'  => $bookings->filter(fn ($b) => $b->situacaoPagamento() === 'atrasado')->values(),
            default      => $bookings,
        };

        return view('bookings.history', compact('arena', 'bookings', 'situacao', 'origem'));
    }

    /**
     * Reservas de HOJE da arena atual — só as confirmadas (as que vão acontecer).
     */
    public function today()
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        $idsQuadras = $arena->courts()->pluck('id')->all();
        Booking::autoConfirmarExpiradas($idsQuadras);
        Booking::autoCompletarRealizadas($idsQuadras);

        $bookings = Booking::with(['court', 'client.user'])
            ->whereIn('court_id', $arena->courts()->select('id'))
            ->whereDate('date', now()->toDateString())
            ->where('status', 'confirmed')
            ->orderBy('start_time')
            ->get();

        return view('bookings.today', compact('arena', 'bookings'));
    }

    /**
     * Reservas AGUARDANDO CONFIRMAÇÃO (pendentes) da arena atual. Aqui o dono
     * confirma ou cancela. Se não fizer nada, elas são confirmadas
     * automaticamente ao expirar o prazo (autoConfirmarExpiradas).
     */
    public function pending()
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        $idsQuadras = $arena->courts()->pluck('id')->all();
        Booking::autoConfirmarExpiradas($idsQuadras);
        Booking::autoCompletarRealizadas($idsQuadras);

        $bookings = Booking::with(['court', 'client.user'])
            ->whereIn('court_id', $arena->courts()->select('id'))
            ->where('status', 'pending')
            ->orderBy('date')->orderBy('start_time')
            ->get();

        // Quantas reservas o cliente já deixou SEM pagamento nesta arena
        // (horário passou e nunca foi pago) — sinal de risco antes de confirmar.
        $naoPagasPorCliente = Booking::whereIn('court_id', $idsQuadras)
            ->where('status', 'completed')
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->selectRaw('client_id, COUNT(*) as total')
            ->groupBy('client_id')
            ->pluck('total', 'client_id')
            ->all();

        return view('bookings.pending', compact('arena', 'bookings', 'naoPagasPorCliente'));
    }

    /**
     * Confirma uma reserva pendente (ação do dono).
     */
    public function confirm(Booking $booking)
    {
        $this->guardBooking($booking);

        if ($booking->status !== 'pending') {
            return back()->withErrors(['acao' => 'Só reservas pendentes podem ser confirmadas.']);
        }

        $booking->update(['status' => 'confirmed']);
        $booking->notificarClienteConfirmada(auth()->id());

        return redirect()->route('bookings.pending')->with('msg', 'Reserva confirmada.');
    }

    /**
     * Cancela uma reserva pendente/confirmada (ação do staff). Se a reserva já
     * estava PAGA, reembolsa o cliente INTEGRALMENTE — quem cancelou foi a arena,
     * então o cliente não é penalizado (sem taxa).
     */
    public function cancel(Request $request, Booking $booking)
    {
        $this->guardBooking($booking);

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->withErrors(['acao' => 'Esta reserva não pode ser cancelada.']);
        }

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            'motivo.required' => 'Informe o motivo do cancelamento.',
        ]);

        $booking->loadMissing('payments', 'court.arena');
        $paga = $booking->isPaga();

        $pagamento = DB::transaction(function () use ($booking, $validated, $paga) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['motivo'],
            ]);

            // Reserva paga cancelada pela arena: reembolso integral (sem taxa).
            return $paga ? PaymentService::reembolsar($booking, 0.0, auth()->id()) : null;
        });

        $booking->notificarClienteCancelada($validated['motivo'], auth()->id());

        if ($pagamento) {
            $reembolso = (float) $pagamento->refund_amount;
            $booking->notificarClienteReembolso($reembolso, 0.0, auth()->id());

            return back()->with('msg',
                'Reserva cancelada. Reembolso de R$ ' . number_format($reembolso, 2, ',', '.') . ' processado.');
        }

        // Volta para a página de origem (próximos ou aguardando confirmação).
        return back()->with('msg', 'Reserva cancelada.');
    }

    /**
     * Tela de reagendamento: escolher nova data e horário livre (mesma quadra).
     */
    public function editSchedule(Request $request, Booking $booking)
    {
        $this->guardBooking($booking);

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect()->route('bookings.index')
                ->withErrors(['acao' => 'Só reservas pendentes ou confirmadas podem ser reagendadas.']);
        }

        $booking->load(['court.arena.businessHours', 'client.user']);
        $court = $booking->court;
        $arena = $court->arena;

        // Abre na data atual da reserva (ou na data pesquisada).
        $date = $request->query('date', $booking->date->toDateString());
        $weekday = Carbon::parse($date)->dayOfWeek;
        $aberto = $arena->businessHours->where('day_of_week', $weekday)->isNotEmpty();
        $slots = $aberto ? CourtScheduleService::slotsDoDia($court, $arena, $date, $booking->id) : collect();

        return view('bookings.edit-schedule', [
            'booking' => $booking,
            'arena' => $arena,
            'court' => $court,
            'date' => $date,
            'aberto' => $aberto,
            'slots' => $slots,
            'diasAbertos' => CourtScheduleService::diasAbertos($arena),
            'numeroReserva' => $booking->numeroNaArena(),
        ]);
    }

    /**
     * Salva o reagendamento (nova data/horário), validando disponibilidade.
     * Mantém o status atual da reserva.
     */
    public function updateSchedule(Request $request, Booking $booking)
    {
        $this->guardBooking($booking);

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect()->route('bookings.index')
                ->withErrors(['acao' => 'Só reservas pendentes ou confirmadas podem ser reagendadas.']);
        }

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'horario' => ['required', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
        ], [
            'horario.required' => 'Selecione um horário.',
            'horario.regex' => 'O horário selecionado é inválido.',
        ]);

        [$startTime, $endTime] = explode('-', $validated['horario']);

        $booking->load('court.arena.businessHours');

        // O horário precisa ser um slot livre e válido (mesma fonte do cliente).
        $slots = CourtScheduleService::slotsDoDia(
            $booking->court,
            $booking->court->arena,
            $validated['date'],
            $booking->id
        );

        $disponivel = $slots->contains(fn ($slot) =>
            ! $slot['ocupado']
            && $slot['start'] === $startTime
            && $slot['end'] === $endTime
        );

        if (! $disponivel) {
            return back()
                ->withErrors(['horario' => 'Esse horário não está disponível. Escolha outro.'])
                ->withInput();
        }

        $booking->update([
            'date' => $validated['date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        $booking->notificarClienteReagendada(auth()->id());

        return redirect()->route('bookings.index')->with('msg', 'Reserva reagendada.');
    }

    /**
     * Garante que a reserva é de uma quadra da arena que o usuário gerencia agora
     * (dono na arena selecionada, ou gerente na arena dele).
     */
    private function guardBooking(Booking $booking): void
    {
        $arena = ArenaAtual::obter();

        abort_unless($booking->court?->arena_id === $arena->id, 403);
    }
}
