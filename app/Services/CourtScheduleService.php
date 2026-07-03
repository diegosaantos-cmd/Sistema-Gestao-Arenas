<?php

namespace App\Services;

use App\Models\Arena;
use App\Models\Booking;
use App\Models\Court;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Grade de horários de uma quadra: fonte única usada tanto na reserva do
 * cliente quanto no reagendamento pelo dono. Deriva os blocos do horário de
 * funcionamento da arena e marca os já ocupados (reservas pendentes/confirmadas).
 */
class CourtScheduleService
{
    /**
     * Blocos de 1h da quadra numa data, cada um com a flag 'ocupado'.
     * O último bloco é cortado no fechamento (ex.: 23:00–23:59). Blocos de hoje
     * que já passaram não entram. $ignoreBookingId permite ignorar a própria
     * reserva (ao reagendar, ela não deve contar como ocupada).
     */
    public static function slotsDoDia(Court $court, Arena $arena, string $date, ?int $ignoreBookingId = null): Collection
    {
        $dia = Carbon::parse($date);
        $weekday = $dia->dayOfWeek;            // 0 = Domingo ... 6 = Sábado
        $isHoje = $dia->isToday();
        $agora = now()->format('H:i');

        $intervalos = $arena->businessHours->where('day_of_week', $weekday);

        $ocupados = Booking::where('court_id', $court->id)
            ->where('date', $dia->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->get(['start_time', 'end_time']);

        $slots = collect();

        foreach ($intervalos as $intervalo) {
            $inicio = Carbon::parse($intervalo->opens_at);
            $fim = Carbon::parse($intervalo->closes_at);

            while ($inicio->lessThan($fim)) {
                $proximo = $inicio->copy()->addHour();
                if ($proximo->greaterThan($fim)) {
                    $proximo = $fim->copy();
                }

                $blocoInicio = $inicio->format('H:i');
                $blocoFim = $proximo->format('H:i');

                $inicio->addHour();

                if ($isHoje && $blocoInicio <= $agora) {
                    continue;
                }

                $ocupado = $ocupados->first(function ($b) use ($blocoInicio, $blocoFim) {
                    return substr($b->start_time, 0, 5) < $blocoFim
                        && substr($b->end_time, 0, 5) > $blocoInicio;
                }) !== null;

                $slots->push([
                    'start' => $blocoInicio,
                    'end' => $blocoFim,
                    'ocupado' => $ocupado,
                ]);
            }
        }

        return $slots;
    }

    /**
     * Primeiro dia (a partir de hoje, até 60 à frente) em que a arena abre E a
     * quadra ainda tem ao menos um horário livre. Cai no diaPadrao se não achar.
     */
    public static function primeiroDiaComHorario(Court $court, Arena $arena, ?int $ignoreBookingId = null): string
    {
        for ($i = 0; $i < 60; $i++) {
            $d = now()->addDays($i);

            if ($arena->businessHours->where('day_of_week', $d->dayOfWeek)->isEmpty()) {
                continue; // fechado nesse dia
            }

            $slots = self::slotsDoDia($court, $arena, $d->toDateString(), $ignoreBookingId);

            if ($slots->contains(fn ($s) => ! $s['ocupado'])) {
                return $d->toDateString();
            }
        }

        return self::diaPadrao($arena);
    }

    /**
     * Dia padrão: hoje, se a arena abrir hoje; senão o próximo dia (até 7 à
     * frente) em que ela abre. Cai em hoje se não houver horários.
     */
    public static function diaPadrao(Arena $arena): string
    {
        $diasAbertos = $arena->businessHours->pluck('day_of_week')->unique();

        for ($i = 0; $i < 7; $i++) {
            $d = now()->addDays($i);
            if ($diasAbertos->contains($d->dayOfWeek)) {
                return $d->toDateString();
            }
        }

        return now()->toDateString();
    }

    /**
     * Dias de funcionamento da arena: [dia_da_semana => 'HH:MM–HH:MM, ...'].
     */
    public static function diasAbertos(Arena $arena): array
    {
        return $arena->businessHours
            ->sortBy([['day_of_week', 'asc'], ['opens_at', 'asc']])
            ->groupBy('day_of_week')
            ->map(fn ($horas) => $horas
                ->map(fn ($h) => substr($h->opens_at, 0, 5) . '–' . substr($h->closes_at, 0, 5))
                ->implode(', '))
            ->toArray();
    }
}