<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Client;
use Illuminate\Http\Request;

/**
 * Arenas favoritas do cliente.
 */
class FavoriteController extends Controller
{
    /**
     * O registro `clients` do usuário logado.
     *
     * Usa firstOrCreate porque contas criadas antes de 2026-07-09 podem não ter
     * a linha em `clients` — ela só nascia na primeira reserva.
     */
    private function clienteAtual(): Client
    {
        $usuario = auth()->user();

        // Só cliente favorita. Sem isto, um dono navegando pela área do cliente
        // ganharia um registro em `clients` ao clicar no coração.
        abort_unless($usuario->type === 'client', 403, 'Apenas clientes podem favoritar arenas.');

        return Client::firstOrCreate(
            ['user_id' => $usuario->id],
            ['date_of_birth' => null]
        );
    }

    /**
     * Tela "Minhas arenas favoritas".
     */
    public function index()
    {
        $cliente = $this->clienteAtual();

        $arenas = $cliente->favoritas()
            ->where('arenas.active', true)
            ->withCount([
                'courts as quadras_ativas_count' => fn ($q) => $q->where('active', true),
            ])
            ->get();

        // O card de arena usa esta lista para desenhar o coração preenchido.
        $favoritasIds = $arenas->pluck('id')->all();

        return view('client.arenas.favoritas', compact('arenas', 'favoritasIds'));
    }

    /**
     * O mesmo botão favorita e desfavorita.
     *
     * O índice único (client_id, arena_id) impede duplicata no banco, mesmo com
     * duplo clique — o `toggle` do Eloquent já trata, mas a garantia real está lá.
     */
    public function toggle(Request $request, Arena $arena)
    {
        abort_unless($arena->active, 404);

        $cliente = $this->clienteAtual();
        $resultado = $cliente->favoritas()->toggle($arena->id);

        $adicionou = ! empty($resultado['attached']);

        $msg = $adicionou
            ? "\"{$arena->name}\" foi adicionada às suas favoritas."
            : "\"{$arena->name}\" foi removida das suas favoritas.";

        // AJAX (o botão de coração): responde JSON e a tela troca o ícone no
        // lugar, sem recarregar. Sem JS, cai no redirect normal abaixo.
        if ($request->wantsJson()) {
            return response()->json([
                'favorited' => $adicionou,
                'message' => $msg,
            ]);
        }

        return back()->with('msg', $msg);
    }
}
