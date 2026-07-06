<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Court;
use App\Services\CourtScheduleService;
use App\Services\PaymentService;
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
     * Próximos agendamentos do cliente — só os CONFIRMADOS ainda por vir.
     * Os pendentes ficam na tela "Agendamentos pendentes".
     */
    public function index()
    {
        Booking::autoConfirmarExpiradas();
        Booking::autoCompletarRealizadas();

        $client = Client::where('user_id', auth()->id())->first();

        $proximas = $client
            ? Booking::where('client_id', $client->id)
                ->where('status', 'confirmed')
                ->whereDate('date', '>=', now()->toDateString())
                ->with('court.arena', 'payments', 'paymentMethod')
                ->orderBy('date')->orderBy('start_time')->get()
            : collect();

        return view('client.bookings.index', [
            'proximas' => $proximas,
            'subtitulo' => 'Suas próximas reservas confirmadas',
        ]);
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
                ->where('status', 'confirmed')
                ->where('end_time', '>', now()->format('H:i:s'))
                ->with('court.arena', 'payments', 'paymentMethod')
                ->orderBy('start_time')
                ->get()
            : collect();

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
                ->with('court.arena', 'payments', 'paymentMethod')
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
     * Formulário para alterar a data e o horário de uma reserva.
     */
    public function edit(Request $request, Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);

        if (! $booking->podeSerEditadaPeloCliente()) {
            return redirect()->route($this->origemAgendamentos($request->input('from')))
                ->withErrors(['edit' => 'Este agendamento não pode mais ser editado — o prazo de alteração já expirou.']);
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
            return redirect()->route($this->origemAgendamentos($request->input('from')))
                ->withErrors(['edit' => 'Este agendamento não pode mais ser editado — o prazo de alteração já expirou.']);
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

        // Volta para a tela de origem (próximos, pendentes ou hoje).
        return redirect()->route($this->origemAgendamentos($request->input('from')))
            ->with('status', 'Agendamento atualizado com sucesso.');
    }

    /**
     * Nome de rota de origem válido (próximos/pendentes/hoje) ou o padrão
     * (próximos). Usado para o "voltar" da edição respeitar de onde veio.
     */
    private function origemAgendamentos(?string $from): string
    {
        $validas = ['client.bookings.index', 'client.bookings.pending', 'client.bookings.today'];

        return in_array($from, $validas, true) ? $from : 'client.bookings.index';
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

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            'motivo.required' => 'Informe o motivo do cancelamento.',
        ]);

        // Se há taxa, registra o valor na reserva — ele vira uma cobrança
        // "a receber" no caixa da arena, até o dono/atendente dar baixa.
        $taxa = $regra === 'taxa' ? $booking->valorTaxaCancelamento() : null;

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['motivo'],
            'cancellation_fee_amount' => $taxa,
        ]);

        return back()->with('status', $taxa
            ? 'Reserva cancelada. Uma taxa de R$ ' . number_format($taxa, 2, ',', '.')
                . ' será cobrada (ficará a receber no caixa da arena).'
            : 'Reserva cancelada. Sem taxa.');
    }

    /**
     * Tela de pagamento da reserva (só confirmada e não paga). O cliente escolhe
     * a forma (PIX/cartão simulados ou dinheiro na arena) e confirma.
     */
    public function pay(Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);
        $booking->load('court.arena.paymentMethods', 'paymentMethod', 'payments');

        if ($booking->status !== 'confirmed') {
            return redirect()->route('client.bookings.index')
                ->withErrors(['pay' => 'Só é possível pagar reservas confirmadas.']);
        }
        if ($booking->isPaga()) {
            return redirect()->route('client.bookings.index')
                ->withErrors(['pay' => 'Esta reserva já está paga.']);
        }

        $formas = $booking->court->arena->paymentMethods->where('active', true);

        return view('client.bookings.pay', compact('booking', 'formas'));
    }

    /**
     * Confirma o pagamento (simulado). PIX/cartão -> registra o pagamento
     * (entra no caixa se aberto, senão fica "a lançar"). Dinheiro -> paga na arena.
     */
    public function payConfirm(Request $request, Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);
        $booking->load('court.arena.paymentMethods', 'payments');

        if ($booking->status !== 'confirmed' || $booking->isPaga()) {
            return redirect()->route('client.bookings.index')
                ->withErrors(['pay' => 'Esta reserva não pode ser paga.']);
        }

        $arena = $booking->court->arena;
        $tipos = $arena->paymentMethods->pluck('type')->all();

        $validated = $request->validate([
            'payment_method' => ['required', Rule::in($tipos)],
        ], [
            'payment_method.in' => 'Forma de pagamento inválida.',
        ]);

        $metodo = $arena->paymentMethods->firstWhere('type', $validated['payment_method']);

        // Guarda a forma escolhida na reserva (pode ter trocado aqui).
        $booking->update(['payment_method_id' => $metodo->id]);

        // Dinheiro: não paga online, acerta na arena.
        if ($metodo->type === 'cash') {
            return redirect()->route('client.bookings.index')
                ->with('status', 'Forma alterada para dinheiro — você paga na arena ao usar o horário.');
        }

        // PIX/cartão: pagamento simulado -> registra (a integração com o caixa
        // é interna; o cliente só precisa saber que foi pago).
        PaymentService::registrar($booking, $metodo, (float) $booking->total_amount, 'online', auth()->id());

        return redirect()->route('client.bookings.index')
            ->with('status', 'Pagamento confirmado! Sua reserva está paga. ✅');
    }

    /**
     * Cria a(s) reserva(s) escolhida(s).
     */
    public function store(Request $request, Arena $arena, Court $court)
    {
        $this->guard($arena, $court);

        $arena->load('paymentMethods');
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'horarios' => ['required', 'array', 'min:1'],
            'horarios.*' => ['required', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
        ], [
            'horarios.required' => 'Selecione ao menos um horário.',
            'horarios.min' => 'Selecione ao menos um horário.',
        ]);

        // O responsável é o próprio cliente logado: usamos os dados do cadastro.
        $user = auth()->user();

        $client = Client::firstOrCreate(
            ['user_id' => $user->id],
            ['date_of_birth' => null]
        );

        // A forma de pagamento NÃO é escolhida aqui — o cliente escolhe ao pagar,
        // depois de a reserva ser confirmada.
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
                    . ' | Telefone: ' . ($user->phone ?: '—'),
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
}
