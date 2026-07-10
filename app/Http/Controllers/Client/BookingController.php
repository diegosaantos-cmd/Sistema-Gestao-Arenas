<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Court;
use App\Services\CourtScheduleService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
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
     * Pagamentos pendentes: reservas já realizadas (completed) que o cliente
     * ainda não pagou — ele pode pagá-las aqui.
     */
    public function unpaidPayments()
    {
        Booking::autoConfirmarExpiradas();
        Booking::autoCompletarRealizadas();

        $client = Client::where('user_id', auth()->id())->first();

        $proximas = $client
            ? Booking::where('client_id', $client->id)
                ->where('status', 'completed')
                ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
                ->with('court.arena', 'payments', 'paymentMethod')
                ->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc')
                ->get()
            : collect();

        return view('client.bookings.index', [
            'proximas' => $proximas,
            'titulo' => 'Pagamentos pendentes',
            'subtitulo' => 'Reservas já realizadas que ainda não foram pagas — você pode pagá-las aqui.',
            'mensagemVazia' => 'Você não tem pagamentos pendentes. 🎉',
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
            'numeroReserva' => $booking->numeroDoCliente(),
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
     * Cancela uma reserva SEM taxa (grátis). Se houver taxa, redireciona para a
     * tela de pagamento da taxa (só cancela pagando online).
     */
    public function cancel(Request $request, Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);

        $regra = $booking->regraCancelamentoCliente();

        if ($regra === null) {
            return back()->withErrors(['cancel' => 'Esta reserva não pode mais ser cancelada.']);
        }

        // Com taxa: só cancela pagando a taxa online.
        if ($regra === 'taxa') {
            return redirect()->route('client.bookings.cancel-pay', $booking);
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
            'cancellation_fee_amount' => null,
        ]);

        return back()->with('status', 'Reserva cancelada. Sem taxa.');
    }

    /**
     * Tela de cancelamento COM taxa: o cliente paga a taxa online (cartão/PIX
     * simulados) para poder cancelar. Se não pagar, a reserva continua.
     */
    public function cancelPay(Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);
        $booking->load('court.arena.paymentMethods', 'payments');

        $regra = $booking->regraCancelamentoCliente();

        if ($regra === null) {
            return redirect()->route('client.bookings.index')
                ->withErrors(['cancel' => 'Esta reserva não pode mais ser cancelada.']);
        }
        // Sem taxa: não precisa desta tela.
        if ($regra !== 'taxa') {
            return redirect()->route('client.bookings.index');
        }

        $taxa = $booking->valorTaxaCancelamento();
        // Só formas online (PIX/cartão) — dinheiro não paga taxa remotamente.
        $formas = $booking->court->arena->paymentMethods
            ->where('active', true)
            ->whereIn('type', ['pix', 'card']);

        $numeroReserva = $booking->numeroDoCliente();

        return view('client.bookings.cancel-pay', compact('booking', 'taxa', 'formas', 'numeroReserva'));
    }

    /**
     * Confirma o pagamento da taxa (simulado) e cancela a reserva.
     */
    public function cancelPayConfirm(Request $request, Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);
        $booking->load('court.arena.paymentMethods', 'payments');

        $regra = $booking->regraCancelamentoCliente();
        if ($regra !== 'taxa') {
            return redirect()->route('client.bookings.index')
                ->withErrors(['cancel' => 'Esta reserva não exige pagamento de taxa para cancelar.']);
        }

        $arena = $booking->court->arena;
        $tiposOnline = $arena->paymentMethods
            ->where('active', true)
            ->whereIn('type', ['pix', 'card'])
            ->pluck('type')->all();

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in($tiposOnline)],
        ], [
            'motivo.required' => 'Informe o motivo do cancelamento.',
            'payment_method.in' => 'Escolha PIX ou cartão para pagar a taxa.',
        ]);

        $metodo = $arena->paymentMethods->firstWhere('type', $validated['payment_method']);
        $taxa = $booking->valorTaxaCancelamento();

        DB::transaction(function () use ($booking, $metodo, $taxa, $validated) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['motivo'],
                'cancellation_fee_amount' => $taxa,
            ]);

            PaymentService::registrar(
                $booking, $metodo, $taxa, 'online', auth()->id(),
                'Taxa de cancelamento reserva #' . $booking->numeroNaArena() . ' — ' . $metodo->label
            );
        });

        return redirect()->route('client.bookings.index')->with('status',
            'Reserva cancelada. Taxa de R$ ' . number_format($taxa, 2, ',', '.') . ' paga. ✅');
    }

    /**
     * Tela de pagamento da reserva (só confirmada e não paga). O cliente escolhe
     * a forma (PIX/cartão simulados ou dinheiro na arena) e confirma.
     */
    public function pay(Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);
        $booking->load('court.arena.paymentMethods', 'paymentMethod', 'payments');

        if (! in_array($booking->status, ['confirmed', 'completed'])) {
            return redirect()->route('client.bookings.index')
                ->withErrors(['pay' => 'Só é possível pagar reservas confirmadas ou já realizadas.']);
        }
        if ($booking->isPaga()) {
            return redirect()->route('client.bookings.index')
                ->withErrors(['pay' => 'Esta reserva já está paga.']);
        }

        $formas = $booking->court->arena->paymentMethods->where('active', true);
        $numeroReserva = $booking->numeroDoCliente();
        $origem = $this->rotaVoltaPagamento(request('origem'));

        return view('client.bookings.pay', compact('booking', 'formas', 'numeroReserva', 'origem'));
    }

    /**
     * Confirma o pagamento (simulado). PIX/cartão -> registra o pagamento
     * (entra no caixa se aberto, senão fica "a lançar"). Dinheiro -> paga na arena.
     */
    public function payConfirm(Request $request, Booking $booking)
    {
        $this->autorizarClienteDaReserva($booking);
        $booking->load('court.arena.paymentMethods', 'payments');

        if (! in_array($booking->status, ['confirmed', 'completed']) || $booking->isPaga()) {
            return redirect()->route('client.bookings.index')
                ->withErrors(['pay' => 'Esta reserva não pode ser paga.']);
        }

        $arena = $booking->court->arena;
        $tipos = $arena->paymentMethods->pluck('type')->all();
        $rotaVolta = $this->rotaVoltaPagamento($request->input('origem'));

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
            return redirect()->route($rotaVolta)
                ->with('status', 'Forma alterada para dinheiro — você paga na arena ao usar o horário.');
        }

        // PIX/cartão: pagamento simulado -> registra (a integração com o caixa
        // é interna; o cliente só precisa saber que foi pago).
        PaymentService::registrar($booking, $metodo, (float) $booking->total_amount, 'online', auth()->id());

        return redirect()->route($rotaVolta)
            ->with('status', 'Pagamento confirmado! Sua reserva está paga. ✅');
    }

    /**
     * Decide para onde voltar depois de pagar, conforme a tela de origem.
     * Ex.: se veio de "Pagamentos pendentes", a mensagem de sucesso aparece
     * na própria tela de pendentes (e não em "Próximos agendamentos").
     * Faz whitelist do nome da rota para evitar redirecionamento indevido.
     */
    private function rotaVoltaPagamento(?string $origem): string
    {
        $validas = [
            'client.bookings.unpaid',
            'client.bookings.pending',
            'client.bookings.today',
            'client.bookings.index',
        ];

        return in_array($origem, $validas, true) ? $origem : 'client.bookings.index';
    }

    /**
     * Cria a(s) reserva(s) escolhida(s).
     */
    public function store(Request $request, Arena $arena, Court $court)
    {
        $this->guard($arena, $court);

        $arena->load(['paymentMethods', 'businessHours']);
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

        // O mesmo bloco pode vir repetido no POST — cada bloco vale uma reserva só.
        $horarios = array_values(array_unique($validated['horarios']));

        try {
            // Tudo ou nada: ou todas as reservas são criadas, ou nenhuma.
            DB::transaction(function () use ($horarios, $validated, $arena, $court, $client, $user) {
                // Serializa pedidos concorrentes para a MESMA quadra: sem isso, duas
                // requisições simultâneas passariam pela checagem e criariam a mesma reserva.
                Court::whereKey($court->id)->lockForUpdate()->first();

                // Grade oficial do dia, recalculada DENTRO do lock. Ela já respeita o
                // horário de funcionamento, os blocos de 1h, os horários que já passaram
                // e os que estão ocupados — então validar contra ela cobre tudo isso.
                $slots = CourtScheduleService::slotsDoDia($court, $arena, $validated['date']);

                // 1) Valida TODOS os horários antes de criar qualquer reserva.
                foreach ($horarios as $horario) {
                    [$startTime, $endTime] = explode('-', $horario);

                    $livre = $slots->contains(fn ($slot) => ! $slot['ocupado']
                        && $slot['start'] === $startTime
                        && $slot['end'] === $endTime
                    );

                    if (! $livre) {
                        throw new \RuntimeException('slot-indisponivel');
                    }
                }

                // 2) Só então cria. A forma de pagamento NÃO é escolhida aqui — o
                // cliente escolhe ao pagar, depois de a reserva ser confirmada.
                foreach ($horarios as $horario) {
                    [$startTime, $endTime] = explode('-', $horario);

                    Booking::create([
                        'court_id' => $court->id,
                        'client_id' => $client->id,
                        'date' => $validated['date'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'total_amount' => $court->hourly_rate,
                        'status' => 'pending',
                        'notes' => 'Responsável: ' . $user->name
                            . ' | Telefone: ' . ($user->phone ?: '—'),
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'slot-indisponivel') {
                throw $e;
            }

            return back()
                ->withErrors(['horarios' => 'Um dos horários selecionados não está mais disponível. Escolha outro.'])
                ->withInput();
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
