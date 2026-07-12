<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->type === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->type === 'owner') {
            return redirect()->route('owners.dashboard');
        }

        if ($user->type === 'employee') {
            // Gerente reaproveita o painel do dono (escopado à arena dele);
            // atendente tem o painel próprio, mais simples.
            if (\App\Support\ArenaAtual::ehGerente()) {
                return redirect()->route('owners.dashboard');
            }

            return redirect()->route('employees.dashboard');
        }

        Booking::autoConfirmarExpiradas();
        Booking::autoCompletarRealizadas();

        $client = Client::where('user_id', $user->id)->first();
        $proximos = collect();
        $proximosCount = 0;
        $hojeCount = 0;
        $pendentes = 0;
        $confirmados = 0;
        $pagamentosPendentes = 0;
        $arenas = Arena::where('active', true)
            ->with('owner.user')
            ->withCount([
                'courts as quadras_ativas_count' => fn ($query) => $query->where('active', true),
            ])
            ->orderBy('name')
            ->limit(6)
            ->get();

        if ($client) {
            $reservasAtivas = Booking::query()
                ->where('client_id', $client->id)
                ->whereDate('date', '>=', today())
                ->whereIn('status', ['pending', 'confirmed']);

            // "Próximos" mostra só os CONFIRMADOS (os pendentes têm card próprio).
            $proximosCount = (clone $reservasAtivas)->where('status', 'confirmed')->count();
            $pendentes = (clone $reservasAtivas)->where('status', 'pending')->count();
            $confirmados = (clone $reservasAtivas)->where('status', 'confirmed')->count();
            // Só as de hoje CONFIRMADAS (não conta pendentes, já realizadas nem
            // canceladas) — assim o número bate com a lista.
            $hojeCount = Booking::where('client_id', $client->id)
                ->whereDate('date', today())
                ->where('status', 'confirmed')
                ->count();

            $proximos = (clone $reservasAtivas)
                ->where('status', 'confirmed')
                ->with('court.arena')
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(4)
                ->get();

            // Reservas realizadas e ainda não pagas (o cliente pode pagar).
            $pagamentosPendentes = Booking::where('client_id', $client->id)
                ->where('status', 'completed')
                ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
                ->count();
        }

        return view('dashboard', compact(
            'pendentes',
            'confirmados',
            'hojeCount',
            'proximosCount',
            'proximos',
            'pagamentosPendentes',
            'arenas'
        ));
    }
}
