<?php

namespace App\Support;

use App\Models\Arena;
use App\Models\Employee;
use App\Models\Owner;

/**
 * Resolve "qual arena o usuário logado está gerenciando agora".
 *
 * Existe para o gerente reaproveitar as MESMAS telas do dono. Antes, cada
 * controller do dono fazia à mão:
 *     $owner = Owner::where('user_id', auth()->id())->first();
 *     $arena = $owner->arenas()->find(session('selected_arena_id'));
 * o que abortava 403 para qualquer um que não fosse dono.
 *
 * Agora a arena atual é:
 *   - DONO: a arena selecionada na sessão, entre as arenas dele (pode trocar).
 *   - GERENTE (employee com access_level 'managerial'): a arena dele, fixa.
 *
 * O atendente (access_level 'basic') NÃO gerencia — só o gerente.
 */
class ArenaAtual
{
    /**
     * A arena gerenciada agora. Aborta se o usuário não tem nenhuma para gerenciar.
     */
    public static function obter(): Arena
    {
        $arena = self::tentar();

        abort_unless($arena, 403, 'Você não tem uma arena para gerenciar.');

        return $arena;
    }

    /**
     * Igual a obter(), mas devolve null em vez de abortar — para quem quer
     * redirecionar em vez de estourar 403 (ex.: o painel).
     */
    public static function tentar(): ?Arena
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if ($user->type === 'owner') {
            $owner = Owner::where('user_id', $user->id)->first();

            return $owner?->arenas()->find(session('selected_arena_id'));
        }

        if ($user->type === 'employee') {
            $gerente = self::gerente();

            return $gerente?->arena;
        }

        return null;
    }

    /**
     * O registro de gerente (Employee managerial e ativo) do usuário logado,
     * ou null se ele não for gerente.
     */
    public static function gerente(): ?Employee
    {
        $user = auth()->user();

        if (! $user || $user->type !== 'employee') {
            return null;
        }

        return Employee::with('arena')
            ->where('user_id', $user->id)
            ->where('access_level', 'managerial')
            ->where('active', true)
            ->first();
    }

    /** Quem está gerenciando é um GERENTE (não o dono)? */
    public static function ehGerente(): bool
    {
        return self::gerente() !== null;
    }

    /** Quem está gerenciando é o DONO da arena? */
    public static function ehDono(): bool
    {
        $user = auth()->user();

        return $user && $user->type === 'owner';
    }

    /**
     * Só o dono cria/exclui arenas, troca de arena e cadastra outro gerente.
     * O gerente gerencia apenas a arena à qual pertence.
     */
    public static function podeGerenciarVariasArenas(): bool
    {
        return self::ehDono();
    }
}
