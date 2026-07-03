<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Court;
use App\Services\CourtScheduleService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
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

        $date = $request->query('date') ?: CourtScheduleService::primeiroDiaComHorario($court, $arena);

        $weekday = Carbon::parse($date)->dayOfWeek;
        $aberto = $arena->businessHours->where('day_of_week', $weekday)->isNotEmpty();
        $slots = $aberto ? CourtScheduleService::slotsDoDia($court, $arena, $date) : collect();

        return view('client.bookings.create', [
            'arena' => $arena,
            'court' => $court,
            'date' => $date,
            'aberto' => $aberto,
            'slots' => $slots,
            'diasAbertos' => CourtScheduleService::diasAbertos($arena),
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
                ->with('court.arena', 'payments')
                ->orderBy('date')->orderBy('start_time')->get()
            : collect();

        $this->prepararTaxasDeCancelamento($proximas);

        return view('client.bookings.index', compact('proximas'));
    }

    /**
     * Todos os agendamentos do cliente marcados para hoje.
     */
    public function today()
    {
        Booking::autoConfirmarExpiradas();
        Booking::autoCompletarRealizadas();

        $client = Client::where('user_id', auth()->id())->first();

        $proximas = $client
            ? Booking::where('client_id', $client->id)
                ->whereDate('date', today())
                ->where('status', '!=', 'cancelled')
                ->with('court.arena')
                ->orderBy('start_time')
                ->get()
            : collect();

        $this->prepararTaxasDeCancelamento($proximas);

        return view('client.bookings.index', [
            'proximas' => $proximas,
            'titulo' => 'Agendamentos de hoje',
            'subtitulo' => 'Suas reservas marcadas para hoje',
            'mensagemVazia' => 'Você não tem agendamentos para hoje.',
        ]);
    }

    /**
     * Agendamentos do cliente que ainda aguardam confirmação.
     */
    public function pending()
    {
        Booking::autoConfirmarExpiradas();
        Booking::autoCompletarRealizadas();

        $client = Client::where('user_id', auth()->id())->first();

        $proximas = $client
            ? Booking::where('client_id', $client->id)
                ->whereDate('date', '>=', today())
                ->where('status', 'pending')
                ->with('court.arena')
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('client.bookings.index', [
            'proximas' => $proximas,
            'titulo' => 'Agendamentos pendentes',
            'subtitulo' => 'Suas reservas que ainda aguardam confirmação',
            'mensagemVazia' => 'Você não tem agendamentos pendentes.',
        ]);
    }

    /**
     * Agendamentos futuros do cliente que já foram confirmados.
     */
    public function confirmed()
    {
        Booking::autoConfirmarExpiradas();
        Booking::autoCompletarRealizadas();

        $client = Client::where('user_id', auth()->id())->first();

        $proximas = $client
            ? Booking::where('client_id', $client->id)
                ->whereDate('date', '>=', today())
                ->where('status', 'confirmed')
                ->with('court.arena')
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
            : collect();

        $this->prepararTaxasDeCancelamento($proximas);

        return view('client.bookings.index', [
            'proximas' => $proximas,
            'titulo' => 'Agendamentos confirmados',
            'subtitulo' => 'Suas reservas que já foram confirmadas',
            'mensagemVazia' => 'Você não tem agendamentos confirmados.',
        ]);
    }

    /**
     * Formulário para alterar a data e o horário de uma reserva.
     */
    public function edit(Request $request, Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);

        if (! $booking->podeSerEditadaPeloCliente()) {
            return redirect()->route('client.bookings.index')
                ->withErrors(['edit' => 'Este agendamento não pode ser editado porque falta menos de 1 hora para o horário reservado.']);
        }

        $booking->load('court.arena.paymentMethods', 'court.arena.businessHours');
        $court = $booking->court;
        $arena = $court->arena;
        $date = $request->query('date', $booking->date->toDateString());
        $weekday = Carbon::parse($date)->dayOfWeek;
        $aberto = $arena->businessHours->where('day_of_week', $weekday)->isNotEmpty();
        $slots = $aberto ? CourtScheduleService::slotsDoDia($court, $arena, $date, $booking->id) : collect();

        return view('client.bookings.edit', [
            'booking' => $booking,
            'arena' => $arena,
            'court' => $court,
            'date' => $date,
            'aberto' => $aberto,
            'slots' => $slots,
            'diasAbertos' => CourtScheduleService::diasAbertos($arena),
        ]);
    }

    /**
     * Atualiza a reserva, mantendo a antecedência mínima de uma hora.
     */
    public function update(Request $request, Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);

        if (! $booking->podeSerEditadaPeloCliente()) {
            return redirect()->route('client.bookings.index')
                ->withErrors(['edit' => 'Este agendamento não pode ser editado porque falta menos de 1 hora para o horário reservado.']);
        }

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'horario' => ['required', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
        ], [
            'horario.required' => 'Selecione um horário.',
            'horario.regex' => 'O horário selecionado é inválido.',
        ]);

        [$startTime, $endTime] = explode('-', $validated['horario']);
        $novoInicio = Carbon::parse($validated['date'] . ' ' . $startTime);

        if ($novoInicio->lt(now()->addHour())) {
            return back()
                ->withErrors(['horario' => 'Escolha um horário com pelo menos 1 hora de antecedência.'])
                ->withInput();
        }

        $booking->load('court.arena.businessHours');
        $slots = CourtScheduleService::slotsDoDia(
            $booking->court,
            $booking->court->arena,
            $validated['date'],
            $booking->id
        );

        $disponivel = $slots->contains(fn ($slot) =>
            ! $slot['ocupado']
            && $slot['start'] === $startTime
            && $slot['end'] === $endTime
        );

        if (! $disponivel) {
            return back()
                ->withErrors(['horario' => 'Esse horário não está disponível. Escolha outro.'])
                ->withInput();
        }

        $booking->update([
            'date' => $validated['date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        return redirect()->route('client.bookings.index')
            ->with('status', 'Agendamento atualizado com sucesso.');
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
                ->with(['court.arena' => fn ($q) => $q->withTrashed(), 'payments'])
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

        $regras = [
            'motivo' => ['required', 'string', 'max:255'],
        ];

        if ($regra === 'taxa') {
            $regras['pagamento_taxa'] = ['accepted'];
        }

        $validated = $request->validate($regras, [
            'motivo.required' => 'Informe o motivo do cancelamento.',
            'pagamento_taxa.accepted' => 'Confirme o pagamento da taxa via PIX para cancelar a reserva.',
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['motivo'],
        ]);

        return back()->with('status', $regra === 'taxa'
            ? 'Pagamento PIX simulado e reserva cancelada com sucesso.'
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

    private function autorizarClienteDaReserva(Booking $booking): void
    {
        $client = Client::where('user_id', auth()->id())->first();

        if (! $client || $booking->client_id !== $client->id) {
            abort(403);
        }
    }

    private function prepararTaxasDeCancelamento($bookings): void
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        foreach ($bookings as $booking) {
            if ($booking->regraCancelamentoCliente() !== 'taxa') {
                continue;
            }

            $valor = $booking->valorTaxaCancelamento();
            $conteudo = implode('|', [
                'PIX-SIMULACAO',
                'RESERVA-' . $booking->id,
                number_format($valor, 2, '.', ''),
                $booking->court->arena->name ?? 'ARENA',
            ]);

            $booking->taxa_cancelamento_percentual = $booking->percentualTaxaCancelamento();
            $booking->taxa_cancelamento_valor = $valor;
            $booking->taxa_cancelamento_qrcode = 'data:image/svg+xml;base64,'
                . base64_encode($writer->writeString($conteudo));
        }
    }
}
