<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Client;
use Illuminate\Http\Request;

class ArenaController extends Controller
{
    /**
     * IDs das arenas favoritas do cliente logado — usados pelo card para
     * desenhar o coração preenchido. Vazio para quem não é cliente.
     */
    private function favoritasIds(): array
    {
        $usuario = auth()->user();

        if (! $usuario || $usuario->type !== 'client') {
            return [];
        }

        $cliente = Client::where('user_id', $usuario->id)->first();

        return $cliente ? $cliente->favoritas()->pluck('arenas.id')->all() : [];
    }

    /**
     * Lista as arenas ativas para o cliente navegar.
     */
    public function index(Request $request)
    {
        $busca = trim((string) $request->query('busca'));

        // A semente mantém a ordem sorteada estável enquanto o cliente navega
        // entre as páginas; sem ela, cada página re-sorteava e repetia arenas.
        $semente = Arena::sementeDaVitrine($request->query('ordem'));

        $arenas = Arena::where('active', true)
            ->pesquisar($busca)
            ->with('owner.user', 'photos')
            ->withCount([
                'courts as quadras_ativas_count' => fn ($query) => $query->where('active', true),
            ])
            ->emOrdemDeVitrine($busca, $semente)
            ->paginate(Arena::POR_PAGINA)
            ->withQueryString()
            ->appends(['ordem' => $semente]);

        $favoritasIds = $this->favoritasIds();

        return view('client.arenas.index', compact('arenas', 'busca', 'favoritasIds'));
    }

    /**
     * Detalhes de uma arena: horários, formas de pagamento e quadras ativas.
     */
    public function show(Arena $arena)
    {
        if (! $arena->active) {
            abort(404);
        }

        $arena->load(['businessHours', 'paymentMethods', 'owner.user', 'photos']);

        $courts = $arena->courts()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $ehFavorita = in_array($arena->id, $this->favoritasIds(), true);

        return view('client.arenas.show', compact('arena', 'courts', 'ehFavorita'));
    }
}
