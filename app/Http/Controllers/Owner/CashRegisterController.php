<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\CashRegister;
use App\Models\CashRegisterEntry;
use App\Models\Owner;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CashRegisterController extends Controller
{
    /**
     * Tela do caixa da arena atual: resumo do caixa aberto (ou botão de abrir)
     * e cards-resumo que levam às páginas de cada seção.
     */
    public function index()
    {
        $arena = $this->arena();

        $caixaAberto = CashRegister::where('arena_id', $arena->id)
            ->where('status', 'open')
            ->with(['user', 'entries'])
            ->first();

        $reservasCount = $caixaAberto ? $this->reservasAReceberQuery($arena)->count() : 0;
        $taxasCount = $caixaAberto ? $this->taxasAReceberQuery($arena)->count() : 0;
        $lancarCount = $caixaAberto ? $this->pagamentosALancarQuery($arena)->count() : 0;
        $lancamentosCount = $caixaAberto ? $caixaAberto->entries->count() : 0;

        // Card "Caixas fechados": conta só os do mês atual e informa o mês.
        $fechadosCount = CashRegister::where('arena_id', $arena->id)
            ->where('status', 'closed')
            ->whereYear('opened_at', now()->year)
            ->whereMonth('opened_at', now()->month)
            ->count();

        $nomesMes = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];
        $mesAtualLabel = $nomesMes[(int) now()->month] . '/' . now()->year;

        return view('owners.caixa.index', compact(
            'arena', 'caixaAberto', 'reservasCount', 'taxasCount', 'lancarCount',
            'lancamentosCount', 'fechadosCount', 'mesAtualLabel'
        ));
    }

    /**
     * Página "Reservas a receber": tabela completa + ação de receber.
     */
    public function receivables()
    {
        $arena = $this->arena();

        $caixaAberto = CashRegister::where('arena_id', $arena->id)
            ->where('status', 'open')
            ->first();

        if (! $caixaAberto) {
            return redirect()->route('caixa.index');
        }

        $reservasAReceber = $this->reservasAReceberQuery($arena)
            ->with(['court', 'client.user'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $formasPagamento = $arena->paymentMethods->where('active', true);
        $numerosReservas = Booking::numerosNaArena($arena->id);

        return view('owners.caixa.receivables', compact(
            'arena', 'caixaAberto', 'reservasAReceber', 'formasPagamento', 'numerosReservas'
        ));
    }

    /**
     * Página "Taxas de cancelamento a receber" (separada das reservas).
     */
    public function fees()
    {
        $arena = $this->arena();

        $caixaAberto = CashRegister::where('arena_id', $arena->id)
            ->where('status', 'open')
            ->first();

        if (! $caixaAberto) {
            return redirect()->route('caixa.index');
        }

        $taxasAReceber = $this->taxasAReceberQuery($arena)
            ->with(['court', 'client.user'])
            ->orderByDesc('cancelled_at')
            ->get();

        $formasPagamento = $arena->paymentMethods->where('active', true);
        $numerosReservas = Booking::numerosNaArena($arena->id);

        return view('owners.caixa.fees', compact(
            'arena', 'caixaAberto', 'taxasAReceber', 'formasPagamento', 'numerosReservas'
        ));
    }

    /**
     * Página "Pagamentos a lançar": pagamentos online que o cliente fez com o
     * caixa fechado e ainda não entraram em nenhum caixa.
     */
    public function pendingPayments()
    {
        $arena = $this->arena();

        $caixaAberto = CashRegister::where('arena_id', $arena->id)
            ->where('status', 'open')
            ->first();

        if (! $caixaAberto) {
            return redirect()->route('caixa.index');
        }

        $pagamentos = $this->pagamentosALancarQuery($arena)
            ->with(['booking.court', 'booking.client.user', 'paymentMethod'])
            ->orderBy('paid_at')
            ->get();

        return view('owners.caixa.pending-payments', compact('arena', 'caixaAberto', 'pagamentos'));
    }

    /**
     * Lança no caixa aberto um pagamento que estava pendente.
     */
    public function launchPayment(Payment $payment)
    {
        $arena = $this->arena();
        $caixa = $this->caixaAberto($arena);

        $booking = $payment->booking;
        $courtIds = $arena->courts()->pluck('id')->all();

        if (! $booking || ! in_array($booking->court_id, $courtIds)) {
            abort(403);
        }

        if ($payment->status !== 'paid' || $payment->origin !== 'online' || $payment->cash_register_entry_id) {
            return back()->withErrors(['pay' => 'Este pagamento já foi lançado no caixa.']);
        }

        PaymentService::lancarNoCaixa($payment, $caixa, auth()->id());

        return redirect()->route('caixa.pending-payments')->with('status', 'Pagamento lançado no caixa.');
    }

    /**
     * Página "Lançamentos": entradas/saídas do caixa aberto + ações.
     */
    public function entries()
    {
        $arena = $this->arena();

        $caixaAberto = CashRegister::where('arena_id', $arena->id)
            ->where('status', 'open')
            ->with(['entries' => fn ($q) => $q->orderByDesc('id'), 'entries.booking'])
            ->first();

        if (! $caixaAberto) {
            return redirect()->route('caixa.index');
        }

        return view('owners.caixa.entries', compact('arena', 'caixaAberto'));
    }

    /**
     * Página "Caixas fechados": histórico com link para cada relatório.
     */
    public function closed()
    {
        $arena = $this->arena();

        $base = CashRegister::where('arena_id', $arena->id)->where('status', 'closed');

        // Meses que realmente têm caixa (baseado na data de abertura). Só esses
        // aparecem no filtro — mês sem nenhum caixa não é listado.
        $nomesMes = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $mesesRaw = (clone $base)
            ->get(['opened_at'])
            ->map(fn ($c) => optional($c->opened_at)->format('Y-m'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $meses = $mesesRaw->map(function ($ym) use ($nomesMes) {
            [$ano, $mes] = explode('-', $ym);
            return ['valor' => $ym, 'label' => $nomesMes[(int) $mes] . '/' . $ano];
        });

        $mesSelecionado = request('mes');

        $query = (clone $base)
            ->with('user')
            ->withCount('entries as lancamentos_count')
            ->withSum(['entries as entradas_sum' => fn ($q) => $q->where('type', 'income')], 'amount')
            ->withSum(['entries as saidas_sum' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->orderByDesc('id');

        if ($mesSelecionado && $mesesRaw->contains($mesSelecionado)) {
            [$ano, $mes] = explode('-', $mesSelecionado);
            $query->whereYear('opened_at', $ano)->whereMonth('opened_at', $mes);
        } else {
            $mesSelecionado = null; // vazio ou inválido -> mostra todos
        }

        $caixasFechados = $query->get();

        $numeros = $this->numerosDaArena($arena);

        return view('owners.caixa.closed', compact(
            'arena', 'caixasFechados', 'meses', 'mesSelecionado', 'numeros'
        ));
    }

    /**
     * Financeiro por mês: entradas, saídas e lucro dos lançamentos do caixa da
     * arena. Mostra os 5 lançamentos mais recentes + botão "ver todos".
     */
    public function report()
    {
        [
            'arena' => $arena, 'meses' => $meses, 'mesSelecionado' => $mesSelecionado,
            'mesLabel' => $mesLabel, 'doMes' => $doMes,
        ] = $this->dadosFinanceiros();

        $entradas = 0;
        $saidas = 0;
        $lancamentos = collect();
        $totalLancamentos = 0;

        if ($doMes) {
            $entradas = (clone $doMes)->where('type', 'income')->sum('amount');
            $saidas = (clone $doMes)->where('type', 'expense')->sum('amount');
            $totalLancamentos = (clone $doMes)->count();
            $lancamentos = (clone $doMes)
                ->with('booking.client.user', 'cashRegister')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }

        $lucro = $entradas - $saidas;

        $numeros = $this->numerosDaArena($arena);

        return view('owners.caixa.report', compact(
            'arena', 'meses', 'mesSelecionado', 'mesLabel',
            'entradas', 'saidas', 'lucro', 'lancamentos', 'totalLancamentos', 'numeros'
        ));
    }

    /**
     * Lista completa dos lançamentos do mês selecionado (página do "ver todos").
     */
    public function reportEntries()
    {
        [
            'arena' => $arena, 'mesSelecionado' => $mesSelecionado,
            'mesLabel' => $mesLabel, 'doMes' => $doMes,
        ] = $this->dadosFinanceiros();

        $entradas = 0;
        $saidas = 0;
        $lancamentos = collect();

        if ($doMes) {
            $entradas = (clone $doMes)->where('type', 'income')->sum('amount');
            $saidas = (clone $doMes)->where('type', 'expense')->sum('amount');
            $lancamentos = (clone $doMes)
                ->with('booking.client.user', 'cashRegister')
                ->orderByDesc('id')
                ->get();
        }

        $lucro = $entradas - $saidas;

        $numeros = $this->numerosDaArena($arena);

        return view('owners.caixa.report-entries', compact(
            'arena', 'mesSelecionado', 'mesLabel',
            'entradas', 'saidas', 'lucro', 'lancamentos', 'numeros'
        ));
    }

    /**
     * Balanço geral da arena: total de entradas, saídas e lucro de todo o
     * período, mais um resumo mês a mês (para acompanhar o total gerado).
     */
    public function balance()
    {
        $arena = $this->arena();

        $entriesBase = CashRegisterEntry::whereIn(
            'cash_register_id',
            CashRegister::where('arena_id', $arena->id)->select('id')
        );

        $totalEntradas = (clone $entriesBase)->where('type', 'income')->sum('amount');
        $totalSaidas = (clone $entriesBase)->where('type', 'expense')->sum('amount');
        $totalLucro = $totalEntradas - $totalSaidas;

        $nomesMes = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        // Resumo mês a mês (mais recente primeiro).
        $porMes = (clone $entriesBase)
            ->get(['type', 'amount', 'created_at'])
            ->groupBy(fn ($e) => optional($e->created_at)->format('Y-m'))
            ->reject(fn ($grupo, $ym) => $ym === '' || $ym === null)
            ->map(function ($grupo, $ym) use ($nomesMes) {
                $ent = $grupo->where('type', 'income')->sum('amount');
                $sai = $grupo->where('type', 'expense')->sum('amount');
                [$ano, $mes] = explode('-', $ym);

                return [
                    'valor' => $ym,
                    'label' => $nomesMes[(int) $mes] . '/' . $ano,
                    'entradas' => $ent,
                    'saidas' => $sai,
                    'lucro' => $ent - $sai,
                ];
            })
            ->sortByDesc('valor')
            ->values();

        return view('owners.caixa.balance', compact(
            'arena', 'totalEntradas', 'totalSaidas', 'totalLucro', 'porMes'
        ));
    }

    /**
     * Dados comuns do financeiro: arena, lista de meses com lançamentos, mês
     * selecionado (pedido/atual/mais recente) e a query dos lançamentos do mês.
     */
    private function dadosFinanceiros(): array
    {
        $arena = $this->arena();

        $entriesBase = CashRegisterEntry::whereIn(
            'cash_register_id',
            CashRegister::where('arena_id', $arena->id)->select('id')
        );

        $nomesMes = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $mesesRaw = (clone $entriesBase)
            ->get(['created_at'])
            ->map(fn ($e) => optional($e->created_at)->format('Y-m'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $meses = $mesesRaw->map(function ($ym) use ($nomesMes) {
            [$ano, $mes] = explode('-', $ym);
            return ['valor' => $ym, 'label' => $nomesMes[(int) $mes] . '/' . $ano];
        });

        // Mês a exibir: o pedido (se válido); senão o atual (se tiver dados);
        // senão o mais recente com lançamentos.
        $mesSelecionado = request('mes');
        if (! $mesSelecionado || ! $mesesRaw->contains($mesSelecionado)) {
            $mesAtual = now()->format('Y-m');
            $mesSelecionado = $mesesRaw->contains($mesAtual) ? $mesAtual : $mesesRaw->first();
        }

        $mesLabel = null;
        $doMes = null;

        if ($mesSelecionado) {
            [$ano, $mes] = explode('-', $mesSelecionado);
            $doMes = (clone $entriesBase)
                ->whereYear('created_at', $ano)
                ->whereMonth('created_at', $mes);
            $mesLabel = $nomesMes[(int) $mes] . '/' . $ano;
        }

        return compact('arena', 'meses', 'mesSelecionado', 'mesLabel', 'doMes');
    }

    /**
     * Abre um novo caixa (só um aberto por arena por vez).
     */
    public function open(Request $request)
    {
        $arena = $this->arena();

        if (! $arena->active) {
            return redirect()->route('owners.dashboard')
                ->with('aviso', 'A arena "' . $arena->name . '" está inativa. Reative-a para abrir o caixa.');
        }

        if (CashRegister::where('arena_id', $arena->id)->where('status', 'open')->exists()) {
            return back()->withErrors(['caixa' => 'Já existe um caixa aberto para esta arena.']);
        }

        $validated = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'opening_balance.required' => 'Informe o troco inicial (use 0 se não houver).',
            'opening_balance.numeric' => 'O troco inicial precisa ser um valor.',
        ]);

        CashRegister::create([
            'arena_id' => $arena->id,
            'user_id' => auth()->id(),
            'opening_balance' => $validated['opening_balance'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'open',
        ]);

        return redirect()->route('caixa.index')->with('status', 'Caixa aberto com sucesso.');
    }

    /**
     * Lançamento manual: entrada (income) ou saída (expense) avulsa.
     */
    public function entry(Request $request)
    {
        $arena = $this->arena();
        $caixa = $this->caixaAberto($arena);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
        ], [
            'amount.min' => 'O valor precisa ser maior que zero.',
            'description.required' => 'Descreva o lançamento.',
        ]);

        CashRegisterEntry::create([
            'cash_register_id' => $caixa->id,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('caixa.entries')->with('status', 'Lançamento registrado.');
    }

    /**
     * Registra o pagamento (manual) de uma reserva: cria o Payment e a entrada
     * no caixa aberto, num único passo.
     */
    public function pay(Request $request, Booking $booking)
    {
        $arena = $this->arena();
        $caixa = $this->caixaAberto($arena);

        // A reserva precisa ser de uma quadra desta arena.
        $courtIds = $arena->courts()->pluck('id')->all();
        if (! in_array($booking->court_id, $courtIds)) {
            abort(403);
        }

        if ($booking->isPaga()) {
            return back()->withErrors(['pay' => 'Esta reserva já foi paga.']);
        }

        $validated = $request->validate([
            'payment_method_id' => ['required', 'integer'],
        ], [
            'payment_method_id.required' => 'Escolha a forma de pagamento.',
        ]);

        // A forma de pagamento precisa ser uma das aceitas pela arena.
        $metodo = $arena->paymentMethods->firstWhere('id', (int) $validated['payment_method_id']);
        if (! $metodo) {
            return back()->withErrors(['pay' => 'Forma de pagamento inválida.']);
        }

        // O valor é fixo: vem da reserva, não do formulário (não pode ser alterado).
        $valor = $booking->total_amount;

        DB::transaction(function () use ($booking, $metodo, $valor, $caixa) {
            $entry = CashRegisterEntry::create([
                'cash_register_id' => $caixa->id,
                'booking_id' => $booking->id,
                'type' => 'income',
                'amount' => $valor,
                'description' => 'Pagamento reserva #' . $booking->numeroNaArena() . ' — ' . $metodo->label,
                'created_by' => auth()->id(),
            ]);

            Payment::create([
                'booking_id' => $booking->id,
                'payment_method_id' => $metodo->id,
                'amount' => $valor,
                'status' => 'paid',
                'origin' => 'local',
                'cash_register_entry_id' => $entry->id,
                'paid_at' => now(),
            ]);
        });

        return redirect()->route('caixa.receivables')->with('status', 'Pagamento registrado no caixa.');
    }

    /**
     * Dá baixa numa TAXA DE CANCELAMENTO (reserva cancelada com taxa): registra
     * o pagamento e lança a entrada no caixa. O valor é fixo (a taxa da reserva).
     */
    public function payFee(Request $request, Booking $booking)
    {
        $arena = $this->arena();
        $caixa = $this->caixaAberto($arena);

        $courtIds = $arena->courts()->pluck('id')->all();
        if (! in_array($booking->court_id, $courtIds)) {
            abort(403);
        }

        // Precisa ser uma reserva cancelada com taxa e ainda não recebida.
        if ($booking->status !== 'cancelled' || (float) $booking->cancellation_fee_amount <= 0) {
            return back()->withErrors(['pay' => 'Esta reserva não tem taxa a receber.']);
        }
        if ($booking->isPaga()) {
            return back()->withErrors(['pay' => 'Esta taxa já foi recebida.']);
        }

        $validated = $request->validate([
            'payment_method_id' => ['required', 'integer'],
        ], [
            'payment_method_id.required' => 'Escolha a forma de pagamento.',
        ]);

        $metodo = $arena->paymentMethods->firstWhere('id', (int) $validated['payment_method_id']);
        if (! $metodo) {
            return back()->withErrors(['pay' => 'Forma de pagamento inválida.']);
        }

        $valor = (float) $booking->cancellation_fee_amount;

        DB::transaction(function () use ($booking, $metodo, $valor, $caixa) {
            $entry = CashRegisterEntry::create([
                'cash_register_id' => $caixa->id,
                'booking_id' => $booking->id,
                'type' => 'income',
                'amount' => $valor,
                'description' => 'Taxa de cancelamento reserva #' . $booking->numeroNaArena() . ' — ' . $metodo->label,
                'created_by' => auth()->id(),
            ]);

            Payment::create([
                'booking_id' => $booking->id,
                'payment_method_id' => $metodo->id,
                'amount' => $valor,
                'status' => 'paid',
                'origin' => 'local',
                'cash_register_entry_id' => $entry->id,
                'paid_at' => now(),
            ]);
        });

        return redirect()->route('caixa.fees')->with('status', 'Taxa de cancelamento recebida.');
    }

    /**
     * Fecha o caixa aberto, gravando o saldo final apurado.
     */
    public function close(Request $request)
    {
        $arena = $this->arena();
        $caixa = $this->caixaAberto($arena);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $caixa->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closing_balance' => $caixa->saldoAtual(),
            'notes' => $validated['notes'] ?: $caixa->notes,
        ]);

        return redirect()->route('caixa.index')->with('status', 'Caixa fechado.');
    }

    /**
     * Relatório (somente leitura) de um caixa fechado.
     */
    public function show(CashRegister $caixa)
    {
        $arena = $this->arena();

        if ($caixa->arena_id !== $arena->id) {
            abort(403);
        }

        $caixa->load([
            'user',
            'entries' => fn ($q) => $q->orderBy('id'),
            'entries.booking.client.user',
        ]);

        $numeros = $this->numerosDaArena($arena);

        return view('owners.caixa.show', compact('arena', 'caixa', 'numeros'));
    }

    /**
     * Detalhes completos de um lançamento do caixa (transparência): tipo, valor,
     * quem fez, quando, o caixa, e — se for pagamento de reserva — os dados da
     * reserva e de como foi pago.
     */
    public function showEntry(CashRegisterEntry $entry)
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        $entry->load([
            'cashRegister.arena',
            'cashRegister.user',
            'createdBy',
            'booking.court',
            'booking.client.user',
            'booking.payments.paymentMethod',
        ]);

        $arenaId = $entry->cashRegister?->arena_id;
        if (! $owner || ! $arenaId || ! $owner->arenas()->whereKey($arenaId)->exists()) {
            abort(403);
        }

        // Pagamento vinculado a esta entrada (quando é lançamento de reserva).
        $pagamento = $entry->booking
            ? $entry->booking->payments->firstWhere('cash_register_entry_id', $entry->id)
            : null;

        $numeros = $this->numerosDaArena($entry->cashRegister->arena);
        $numeroReserva = $entry->booking?->numeroNaArena();

        return view('owners.caixa.entry-details', compact('entry', 'pagamento', 'numeros', 'numeroReserva'));
    }

    /**
     * Query base das reservas desta arena ainda sem pagamento confirmado.
     * Só confirmadas/realizadas — pendentes ainda não podem ser pagas.
     */
    private function reservasAReceberQuery(Arena $arena)
    {
        $courtIds = $arena->courts()->pluck('id');

        return Booking::whereIn('court_id', $courtIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'));
    }

    /**
     * Query base das TAXAS DE CANCELAMENTO a receber: reservas canceladas com
     * taxa (> 0) que ainda não foram pagas.
     */
    private function taxasAReceberQuery(Arena $arena)
    {
        $courtIds = $arena->courts()->pluck('id');

        return Booking::whereIn('court_id', $courtIds)
            ->where('status', 'cancelled')
            ->where('cancellation_fee_amount', '>', 0)
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'));
    }

    /**
     * Query base dos pagamentos pagos (online) que ainda não foram lançados em
     * nenhum caixa (feitos com o caixa fechado).
     */
    private function pagamentosALancarQuery(Arena $arena)
    {
        $courtIds = $arena->courts()->pluck('id');

        return Payment::where('status', 'paid')
            ->where('origin', 'online')
            ->whereNull('cash_register_entry_id')
            ->whereIn('booking_id', Booking::whereIn('court_id', $courtIds)->select('id'));
    }

    /**
     * Mapa [id_do_caixa => número sequencial na arena], por ordem de abertura.
     * Assim cada arena tem seus caixas numerados 1, 2, 3... independente do id
     * do banco.
     */
    private function numerosDaArena(Arena $arena): array
    {
        return CashRegister::where('arena_id', $arena->id)
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->flip()
            ->map(fn ($pos) => $pos + 1)
            ->all();
    }

    /**
     * Arena que o dono está gerenciando (validada como dele). Já traz as formas
     * de pagamento carregadas para reuso.
     */
    private function arena(): Arena
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner?->arenas()->find(session('selected_arena_id'));

        abort_unless($arena, 403, 'Selecione uma arena para gerenciar o caixa.');

        $arena->load('paymentMethods');

        return $arena;
    }

    /**
     * O caixa aberto da arena (ou 403 se não houver).
     */
    private function caixaAberto(Arena $arena): CashRegister
    {
        $caixa = CashRegister::where('arena_id', $arena->id)
            ->where('status', 'open')
            ->first();

        abort_unless($caixa, 403, 'Não há caixa aberto.');

        return $caixa;
    }
}