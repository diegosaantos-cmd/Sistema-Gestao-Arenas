<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Owner;
use App\Services\CourtScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Todos os próximos agendamentos da arena atual (de hoje em diante,
     * ignorando cancelados) — tela própria pra não alongar o dashboard.
     */
    public function index()
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem ver os agendamentos.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

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
                    $qb->whereHas('client.user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
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
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem ver os agendamentos.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

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

        if ($q !== '') {
            $query->where(function ($qb) use ($campo, $q) {
                if ($campo === 'quadra') {
                    $qb->whereHas('court', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                } elseif ($campo === 'data') {
                    $qb->whereDate('date', $q);
                } else {
                    $qb->whereHas('client.user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                }
            });
        }

        $bookings = $query->orderBy('date', 'desc')->orderBy('start_time', 'desc')->get();

        return view('bookings.history', compact('arena', 'bookings'));
    }

    /**
     * Reservas de HOJE da arena atual — só as confirmadas (as que vão acontecer).
     */
    public function today()
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem ver os agendamentos.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

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
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem ver os agendamentos.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

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

        return view('bookings.pending', compact('arena', 'bookings'));
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

        return redirect()->route('bookings.pending')->with('msg', 'Reserva confirmada.');
    }

    /**
     * Cancela uma reserva pendente/confirmada (ação do dono, sem taxa).
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

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['motivo'],
        ]);

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

        return redirect()->route('bookings.index')->with('msg', 'Reserva reagendada.');
    }

    /**
     * Garante que a reserva é de uma quadra de uma arena do dono logado.
     */
    private function guardBooking(Booking $booking): void
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $booking->court?->arena;

        if (! $owner || ! $arena || ! $owner->arenas()->whereKey($arena->id)->exists()) {
            abort(403);
        }
    }
}
