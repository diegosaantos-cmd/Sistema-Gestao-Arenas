<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Court;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * Tela de reserva: já abre num dia válido e mostra a grade de horários
     * (livres e ocupados) da quadra naquele dia.
     */
    public function create(Request $request, Arena $arena, Court $court)
    {
        $this->guard($arena, $court);

        $arena->load(['paymentMethods', 'businessHours']);

        $date = $request->query('date') ?: $this->primeiroDiaComHorario($court, $arena);

        $weekday = Carbon::parse($date)->dayOfWeek;
        $aberto = $arena->businessHours->where('day_of_week', $weekday)->isNotEmpty();
        $slots = $aberto ? $this->slotsDoDia($court, $arena, $date) : collect();

        return view('client.bookings.create', [
            'arena' => $arena,
            'court' => $court,
            'date' => $date,
            'aberto' => $aberto,
            'slots' => $slots,
            'diasAbertos' => $this->diasAbertos($arena),
        ]);
    }

    /**
     * Próximos agendamentos do cliente (pendentes/confirmados ainda por vir).
     */
    public function index()
    {
        Booking::autoConfirmarExpiradas();
        Booking::autoCompletarRealizadas();

        $client = Client::where('user_id', auth()->id())->first();

        $proximas = $client
            ? Booking::where('client_id', $client->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('date', '>=', now()->toDateString())
                ->with('court.arena')
                ->orderBy('date')->orderBy('start_time')->get()
            : collect();

        return view('client.bookings.index', compact('proximas'));
    }

    /**
     * Histórico do cliente (canceladas e realizadas).
     */
    public function history()
    {
        $client = Client::where('user_id', auth()->id())->first();

        $historico = $client
            ? Booking::where('client_id', $client->id)
                ->whereIn('status', ['cancelled', 'completed'])
                ->with('court.arena')
                ->orderBy('date', 'desc')->orderBy('start_time', 'desc')->get()
            : collect();

        return view('client.bookings.history', compact('historico'));
    }

    /**
     * Cancela uma reserva do próprio cliente, respeitando a regra de cancelamento.
     */
    public function cancel(Request $request, Booking $booking)
    {
        $client = Client::where('user_id', auth()->id())->first();

        if (! $client || $booking->client_id !== $client->id) {
            abort(403);
        }

        $regra = $booking->regraCancelamentoCliente();

        if ($regra === null) {
            return back()->withErrors(['cancel' => 'Esta reserva não pode mais ser cancelada.']);
        }

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            'motivo.required' => 'Informe o motivo do cancelamento.',
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['motivo'],
        ]);

        return back()->with('status', $regra === 'taxa'
            ? 'Reserva cancelada. Uma taxa será aplicada (valor a definir).'
            : 'Reserva cancelada.');
    }

    /**
     * Cria a(s) reserva(s) escolhida(s).
     */
    public function store(Request $request, Arena $arena, Court $court)
    {
        $this->guard($arena, $court);

        $arena->load('paymentMethods');
        $tipos = $arena->paymentMethods->pluck('type')->all();

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'horarios' => ['required', 'array', 'min:1'],
            'horarios.*' => ['required', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
            'payment_method' => ['required', Rule::in($tipos)],
        ], [
            'horarios.required' => 'Selecione ao menos um horário.',
            'horarios.min' => 'Selecione ao menos um horário.',
            'payment_method.in' => 'Forma de pagamento inválida.',
        ]);

        // O responsável é o próprio cliente logado: usamos os dados do cadastro.
        $user = auth()->user();

        $client = Client::firstOrCreate(
            ['user_id' => $user->id],
            ['date_of_birth' => null]
        );

        $pagamento = $arena->paymentMethods->firstWhere('type', $validated['payment_method'])?->label
            ?? $validated['payment_method'];

        foreach ($validated['horarios'] as $horario) {
            [$startTime, $endTime] = explode('-', $horario);

            $conflito = Booking::where('court_id', $court->id)
                ->where('date', $validated['date'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->exists();

            if ($conflito) {
                return back()
                    ->withErrors(['horarios' => 'Um dos horários selecionados já foi reservado. Escolha outro.'])
                    ->withInput();
            }

            Booking::create([
                'court_id' => $court->id,
                'client_id' => $client->id,
                'employee_id' => null,
                'date' => $validated['date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_amount' => $court->hourly_rate,
                'status' => 'pending',
                'notes' => 'Responsável: ' . $user->name
                    . ' | Telefone: ' . ($user->phone ?: '—')
                    . ' | Pagamento: ' . $pagamento,
            ]);
        }

        return redirect()->route('client.bookings.success');
    }

    /**
     * A quadra/arena precisa estar ativa e a quadra ser daquela arena.
     */
    private function guard(Arena $arena, Court $court): void
    {
        if (! $arena->active || ! $court->active || $court->arena_id !== $arena->id) {
            abort(404);
        }
    }

    /**
     * Dia padrão da tela: hoje, se a arena abrir hoje; senão o próximo dia
     * (até 7 à frente) em que ela abre. Cai em hoje se não houver horários.
     */
    private function diaPadrao(Arena $arena): string
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
     * Primeiro dia (a partir de hoje) em que a arena abre E a quadra ainda
     * tem ao menos um horário livre. Assim a tela já abre num dia com vaga,
     * pulando dias lotados/sem horário. Cai no diaPadrao se não achar.
     */
    private function primeiroDiaComHorario(Court $court, Arena $arena): string
    {
        for ($i = 0; $i < 60; $i++) {
            $d = now()->addDays($i);

            if ($arena->businessHours->where('day_of_week', $d->dayOfWeek)->isEmpty()) {
                continue; // fechado nesse dia
            }

            $slots = $this->slotsDoDia($court, $arena, $d->toDateString());

            if ($slots->contains(fn ($s) => ! $s['ocupado'])) {
                return $d->toDateString();
            }
        }

        return $this->diaPadrao($arena);
    }

    /**
     * Dias de funcionamento da arena: [dia_da_semana => 'HH:MM–HH:MM, ...'].
     */
    private function diasAbertos(Arena $arena): array
    {
        return $arena->businessHours
            ->sortBy([['day_of_week', 'asc'], ['opens_at', 'asc']])
            ->groupBy('day_of_week')
            ->map(fn ($horas) => $horas
                ->map(fn ($h) => substr($h->opens_at, 0, 5) . '–' . substr($h->closes_at, 0, 5))
                ->implode(', '))
            ->toArray();
    }

    /**
     * Grade de blocos de 1h da quadra numa data: TODOS os blocos dentro do
     * horário de funcionamento, cada um com a flag 'ocupado' (pending/confirmed).
     * Blocos de hoje que já passaram não entram (não dá para reservar o passado).
     */
    private function slotsDoDia(Court $court, Arena $arena, string $date)
    {
        $dia = Carbon::parse($date);
        $weekday = $dia->dayOfWeek;            // 0 = Domingo ... 6 = Sábado
        $isHoje = $dia->isToday();
        $agora = now()->format('H:i');

        $intervalos = $arena->businessHours->where('day_of_week', $weekday);

        $ocupados = Booking::where('court_id', $court->id)
            ->where('date', $dia->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['start_time', 'end_time']);

        $slots = collect();

        foreach ($intervalos as $intervalo) {
            $inicio = Carbon::parse($intervalo->opens_at);
            $fim = Carbon::parse($intervalo->closes_at);

            while ($inicio->copy()->addHour()->lessThanOrEqualTo($fim)) {
                $blocoInicio = $inicio->format('H:i');
                $blocoFim = $inicio->copy()->addHour()->format('H:i');

                $inicio->addHour();

                // Não mostra horários que já passaram (se for hoje).
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
}
