<?php

namespace App\Http\Middleware;

use App\Support\ArenaAtual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Libera só quem GERENCIA a arena (dono ou gerente). O atendente — que também
 * transita pela área "owners" para operar caixa e reservas — é barrado nas
 * telas de gestão (cadastrar/editar/excluir quadras, funcionários, clientes,
 * arena e os relatórios financeiros). Ver App\Support\ArenaAtual::podeGerir().
 */
class PodeGerirArena
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(ArenaAtual::podeGerir(), 403, 'Você não tem permissão para gerenciar esta arena.');

        return $next($request);
    }
}
