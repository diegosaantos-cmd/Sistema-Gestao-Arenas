<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Owner;

class ClientController extends Controller
{
    /**
     * Lista os clientes da arena atual: quem tem ao menos uma reserva nas
     * quadras dela (qualquer status). Mostra também quantas reservas cada um fez.
     */
    public function index()
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem ver os clientes.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        $courtIds = $arena->courts()->select('id');

        // Clientes distintos com reserva nesta arena.
        $clientIds = Booking::whereIn('court_id', $courtIds)
            ->distinct()
            ->pluck('client_id');

        $q = trim((string) request('q'));

        $clients = Client::with('user')
            ->whereIn('id', $clientIds)
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")        // nome: contém
                      ->orWhere('email', 'like', "{$q}%");      // e-mail: começa com (ignora o domínio)
                });
            })
            ->get();

        // Nº de reservas por cliente nesta arena (uma query só, sem N+1).
        $reservasPorCliente = Booking::whereIn('court_id', $courtIds)
            ->selectRaw('client_id, count(*) as total')
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        return view('clients.index', compact('arena', 'clients', 'reservasPorCliente'));
    }
}
