<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Owner;

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

        $query = Booking::with(['court', 'client.user', 'payments'])
            ->whereIn('court_id', $arena->courts()->select('id'))
            ->whereDate('date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed']);

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
}
