<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use Illuminate\Http\Request;

class ArenaController extends Controller
{
    /**
     * Lista as arenas ativas para o cliente navegar.
     */
    public function index(Request $request)
    {
        $busca = trim((string) $request->query('busca'));

        $arenas = Arena::where('active', true)
            ->pesquisar($busca)
            ->with('owner.user')
            ->withCount([
                'courts as quadras_ativas_count' => fn ($query) => $query->where('active', true),
            ])
            ->orderBy('name')
            ->get();

        return view('client.arenas.index', compact('arenas', 'busca'));
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
