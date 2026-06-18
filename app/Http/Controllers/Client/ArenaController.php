<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;

class ArenaController extends Controller
{
    /**
     * Lista as arenas ativas para o cliente navegar.
     */
    public function index()
    {
        $arenas = Arena::where('active', true)
            ->orderBy('name')
            ->get();

        return view('client.arenas.index', compact('arenas'));
    }

    /**
     * Detalhes de uma arena: horários, formas de pagamento e quadras ativas.
     */
    public function show(Arena $arena)
    {
        if (! $arena->active) {
            abort(404);
        }

        $arena->load(['businessHours', 'paymentMethods', 'owner.user']);

        $courts = $arena->courts()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('client.arenas.show', compact('arena', 'courts'));
    }
}
