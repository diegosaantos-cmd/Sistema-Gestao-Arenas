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

        if ($user->type === 'owner') {
            return redirect()->route('owners.dashboard');
        }

        if ($user->type === 'employee') {
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

            $proximosCount = (clone $reservasAtivas)->count();
            $pendentes = (clone $reservasAtivas)->where('status', 'pending')->count();
            $confirmados = (clone $reservasAtivas)->where('status', 'confirmed')->count();
            $hojeCount = Booking::where('client_id', $client->id)
                ->whereDate('date', today())
                ->where('status', '!=', 'cancelled')
                ->count();

            $proximos = (clone $reservasAtivas)
                ->with('court.arena')
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(4)
                ->get();
        }

        return view('dashboard', compact(
            'pendentes',
            'confirmados',
            'hojeCount',
            'proximosCount',
            'proximos',
            'arenas'
        ));
    }
}
