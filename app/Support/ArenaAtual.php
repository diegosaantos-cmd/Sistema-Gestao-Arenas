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
 *   - ATENDENTE (employee com access_level 'basic'): a arena dele, fixa.
 *
 * Dono e gerente GERENCIAM (podeGerir()); o atendente só opera caixa/reservas e
 * consulta a arena — as telas de gestão ficam bloqueadas por rota (pode.gerir).
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
            // Gerente ou atendente: a arena à qual o vínculo ativo pertence.
            return self::empregado()?->arena;
        }

        return null;
    }

    /**
     * O vínculo de funcionário ATIVO do usuário logado (gerente ou atendente),
     * ou null se ele não for funcionário.
     */
    public static function empregado(): ?Employee
    {
        $user = auth()->user();

        if (! $user || $user->type !== 'employee') {
            return null;
        }

        return Employee::with('arena')
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();
    }

    /**
     * O registro de gerente (Employee managerial e ativo) do usuário logado,
     * ou null se ele não for gerente.
     */
    public static function gerente(): ?Employee
    {
        $empregado = self::empregado();

        return $empregado && $empregado->access_level === 'managerial' ? $empregado : null;
    }

    /**
     * O registro de atendente (Employee 'basic' e ativo) do usuário logado,
     * ou null se ele não for atendente.
     */
    public static function atendente(): ?Employee
    {
        $empregado = self::empregado();

        return $empregado && $empregado->access_level !== 'managerial' ? $empregado : null;
    }

    /** Quem está gerenciando é um GERENTE (não o dono)? */
    public static function ehGerente(): bool
    {
        return self::gerente() !== null;
    }

    /** O usuário logado é um ATENDENTE (funcionário sem nível gerencial)? */
    public static function ehAtendente(): bool
    {
        return self::atendente() !== null;
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

    /**
     * Pode acessar as telas de GESTÃO (cadastrar/editar/excluir quadras,
     * funcionários, clientes, arena, e os relatórios financeiros)? Só o dono e o
     * gerente. O atendente é bloqueado por rota (middleware pode.gerir).
     */
    public static function podeGerir(): bool
    {
        return self::ehDono() || self::ehGerente();
    }

    /** Rótulo do painel conforme o papel de quem está logado. */
    public static function rotulo(): string
    {
        if (self::ehDono()) {
            return 'Painel do proprietário';
        }

        if (self::ehGerente()) {
            return 'Painel do gerente';
        }

        if (self::ehAtendente()) {
            return 'Painel do atendente';
        }

        return 'Painel';
    }
}
