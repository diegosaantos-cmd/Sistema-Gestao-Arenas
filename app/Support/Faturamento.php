<?php

namespace App\Support;

use App\Models\CashRegister;
use App\Models\CashRegisterEntry;

/**
 * Faturamento REAL de uma (ou várias) arena(s), calculado a partir do caixa
 * (cash_register_entries) — a fonte que reflete o dinheiro de verdade.
 *
 * Ao contrário do "faturamento" antigo (que somava só os pagamentos de
 * reservas), aqui entram TODAS as entradas e saídas lançadas no caixa:
 *
 *   - reservas       : pagamentos de reserva (income com booking_id, não taxa)
 *   - cancelamentos  : taxas de cancelamento (descrição "Taxa de cancelamento…")
 *   - avulsas        : entradas avulsas (income sem booking_id)
 *   - despesas       : saídas (expense)
 *   - liquido        : (reservas + cancelamentos + avulsas) − despesas
 *
 * A taxa de cancelamento é identificada pela descrição, que é sempre
 * "Taxa de cancelamento reserva #…" (ver Client\BookingController e
 * Owner\CashRegisterController). Entrada avulsa é income sem reserva.
 */
class Faturamento
{
    private const PREFIXO_TAXA = 'Taxa de cancelamento%';

    /**
     * Resumo do faturamento das arenas informadas, opcionalmente num período
     * (datas 'aaaa-mm-dd'). Devolve os totais já somados.
     *
     * @param  array<int>  $arenaIds
     * @return array{reservas: float, cancelamentos: float, avulsas: float, despesas: float, entradas: float, liquido: float}
     */
    public static function resumo(array $arenaIds, ?string $inicio = null, ?string $fim = null): array
    {
        $cashIds = CashRegister::whereIn('arena_id', $arenaIds)->pluck('id');

        if ($cashIds->isEmpty()) {
            return self::zerado();
        }

        $base = CashRegisterEntry::whereIn('cash_register_id', $cashIds)
            ->when($inicio, fn ($q) => $q->whereDate('created_at', '>=', $inicio))
            ->when($fim, fn ($q) => $q->whereDate('created_at', '<=', $fim));

        $reservas = (float) (clone $base)->where('type', 'income')
            ->whereNotNull('booking_id')
            ->where('description', 'not like', self::PREFIXO_TAXA)
            ->sum('amount');

        $cancelamentos = (float) (clone $base)->where('type', 'income')
            ->where('description', 'like', self::PREFIXO_TAXA)
            ->sum('amount');

        $avulsas = (float) (clone $base)->where('type', 'income')
            ->whereNull('booking_id')
            ->where('description', 'not like', self::PREFIXO_TAXA)
            ->sum('amount');

        $despesas = (float) (clone $base)->where('type', 'expense')->sum('amount');

        $entradas = $reservas + $cancelamentos + $avulsas;

        return [
            'reservas'      => $reservas,
            'cancelamentos' => $cancelamentos,
            'avulsas'       => $avulsas,
            'despesas'      => $despesas,
            'entradas'      => $entradas,
            'liquido'       => $entradas - $despesas,
        ];
    }

    /** Só o faturamento líquido (para o número do card). */
    public static function liquido(array $arenaIds, ?string $inicio = null, ?string $fim = null): float
    {
        return self::resumo($arenaIds, $inicio, $fim)['liquido'];
    }

    /**
     * Faturamento líquido por mês (entradas − despesas), do ano informado ou de
     * todos os meses. Uma query só. Devolve uma coleção de objetos com
     * {mes: 'aaaa-mm', entradas, despesas, liquido}, do mais recente ao antigo.
     */
    public static function mensal(array $arenaIds, ?int $ano = null): \Illuminate\Support\Collection
    {
        $cashIds = CashRegister::whereIn('arena_id', $arenaIds)->pluck('id');

        if ($cashIds->isEmpty()) {
            return collect();
        }

        return CashRegisterEntry::whereIn('cash_register_id', $cashIds)
            ->when($ano, fn ($q) => $q->whereYear('created_at', $ano))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as entradas,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as despesas")
            ->groupBy('mes')
            ->orderByDesc('mes')
            ->get()
            ->map(function ($linha) {
                $linha->entradas = (float) $linha->entradas;
                $linha->despesas = (float) $linha->despesas;
                $linha->liquido = $linha->entradas - $linha->despesas;

                return $linha;
            });
    }

    /** Anos que têm lançamento no caixa (para o filtro), sempre incluindo o atual. */
    public static function anos(array $arenaIds): \Illuminate\Support\Collection
    {
        $cashIds = CashRegister::whereIn('arena_id', $arenaIds)->pluck('id');

        return CashRegisterEntry::whereIn('cash_register_id', $cashIds)
            ->selectRaw('DISTINCT YEAR(created_at) as ano')
            ->pluck('ano')
            ->map(fn ($a) => (int) $a)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    private static function zerado(): array
    {
        return [
            'reservas' => 0.0, 'cancelamentos' => 0.0, 'avulsas' => 0.0,
            'despesas' => 0.0, 'entradas' => 0.0, 'liquido' => 0.0,
        ];
    }
}
