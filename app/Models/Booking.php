<?php

namespace App\Models;

use App\Services\PaymentService;
use App\Support\Anonimizacao;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    /** Reserva feita pelo cliente no site. */
    public const ORIGEM_SITE = 'site';

    /** Reserva registrada no balcão da arena pelo dono, gerente ou atendente. */
    public const ORIGEM_PRESENCIAL = 'presencial';

    protected $fillable = [
        'court_id', 'client_id', 'date',
        'start_time', 'end_time', 'total_amount', 'status', 'notes',
        'cancelled_by', 'cancellation_reason', 'cancelled_at', 'cancellation_fee_amount',
        // Reserva presencial: cliente sem cadastro + quem registrou.
        'guest_name', 'guest_phone', 'guest_email', 'created_by', 'origin',
    ];

    protected $casts = [
        'date' => 'date',
        'cancelled_at' => 'datetime',
        'cancellation_fee_amount' => 'decimal:2',
    ];

    public function court()
    {
        // withTrashed: quadra excluída (soft delete) continua aparecendo no
        // histórico de reservas com o nome — o registro não se "perde". As
        // listagens de quadras ativas usam Arena::courts(), que exclui as apagadas.
        return $this->belongsTo(Court::class)->withTrashed();
    }

    public function courtWithTrashed()
    {
        return $this->belongsTo(Court::class, 'court_id')->withTrashed();
    }

    public function client()
    {
        // withTrashed: cliente que virou proprietário (conta de cliente
        // encerrada / soft delete) continua aparecendo no histórico de reservas
        // com o nome. As LISTAS de clientes usam Client::query (sem trashed),
        // então ele deixa de figurar como cliente ativo — "cliente que não existe".
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /**
     * Quem registrou a reserva: dono, gerente ou atendente.
     * Nulo nas reservas feitas pelo próprio cliente no site.
     */
    public function criadoPor()
    {
        // withTrashed: se o funcionário que registrou a reserva for excluído,
        // o histórico continua mostrando QUEM registrou.
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * A reserva foi registrada no balcão da arena?
     */
    public function ehPresencial(): bool
    {
        return $this->origin === self::ORIGEM_PRESENCIAL;
    }

    /**
     * Nome de quem vai jogar — venha ele do cadastro ou do balcão.
     *
     * Existe para as telas não precisarem saber se a reserva tem cliente
     * cadastrado. Sem isto, cada uma das ~65 telas que mostram o nome teria
     * que repetir a mesma verificação.
     */
    /**
     * Marcadores da anonimização. O texto mora em App\Support\Anonimizacao,
     * que é o vocabulário comum a reserva, usuário e arena; aqui ficam só os
     * apelidos, para o código que já os usava continuar valendo.
     */
    public const CLIENTE_EXCLUIDO = Anonimizacao::CLIENTE_EXCLUIDO;

    public const REMOVIDO = Anonimizacao::REMOVIDO;

    public function nomeCliente(): string
    {
        return $this->client?->user?->name
            ?? $this->guest_name
            ?? '—';
    }

    /**
     * Contato do cliente, lido do registro.
     *
     * Não há regra de exibição aqui: quando a conta é encerrada, a própria
     * anonimização grava "Removido" nestes campos (ver
     * Client::desligarReservasAnonimizando). O banco guarda o que a tela mostra.
     */
    public function telefoneCliente(): string
    {
        return $this->client?->user?->phone ?: $this->guest_phone ?: '—';
    }

    public function emailCliente(): string
    {
        return $this->client?->user?->email ?: $this->guest_email ?: '—';
    }

    public function observacoes(): string
    {
        return $this->notes ?: '—';
    }

    /**
     * Número da reserva na sequência do CLIENTE dono dela (1, 2, 3...), na
     * ordem em que ele reservou. Evita expor o id global do banco, que
     * "pularia" (ex.: a 2ª reserva do cliente aparecer como #10).
     */
    public function numeroDoCliente(): int
    {
        // Reserva presencial não pertence a um cliente cadastrado: não há
        // sequência dele. Sem esta guarda, contaria TODAS as presenciais juntas.
        if ($this->client_id === null) {
            return $this->id;
        }

        return static::where('client_id', $this->client_id)
            ->where('id', '<=', $this->id)
            ->count();
    }

    /**
     * Números de todas as reservas de um cliente de uma vez ([id => nº]).
     *
     * Mesma sequência do numeroDoCliente(), mas numa consulta só. Para listas:
     * chamar o método por linha faria um COUNT por reserva (N+1). Espelha o
     * numerosNaArena().
     */
    public static function numerosDoCliente(int $clientId): array
    {
        return static::where('client_id', $clientId)
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->flip()
            ->map(fn ($pos) => $pos + 1)
            ->all();
    }

    /**
     * Número da reserva na sequência da ARENA (1, 2, 3...), na ordem de
     * criação dentro daquela arena. Conta reservas de quadras já excluídas
     * também, para a sequência não pular.
     */
    public function numeroNaArena(): int
    {
        $arenaId = $this->court?->arena_id ?? $this->courtWithTrashed?->arena_id;

        if (! $arenaId) {
            return $this->id;
        }

        return static::whereIn('court_id', static::courtIdsDaArena($arenaId))
            ->where('id', '<=', $this->id)
            ->count();
    }

    /**
     * Mapa [booking_id => número na arena] para todas as reservas de uma
     * arena — usado nas listagens para evitar uma consulta por linha.
     */
    public static function numerosNaArena(int $arenaId): array
    {
        return static::whereIn('court_id', static::courtIdsDaArena($arenaId))
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->flip()
            ->map(fn ($pos) => $pos + 1)
            ->all();
    }

    /**
     * Ids das quadras (inclusive excluídas) de uma arena.
     */
    protected static function courtIdsDaArena(int $arenaId)
    {
        return Court::withTrashed()->where('arena_id', $arenaId)->pluck('id');
    }

    /**
     * Ordenações oferecidas nas listas de reservas (chave da URL => rótulo).
     *
     * O padrão é "num_desc": a lista segue o número da reserva (#N), do maior
     * para o menor. Assim a coluna Nº lê em sequência (…, 3, 2, 1) em vez de
     * embaralhar — o que acontecia quando a lista ia por data e o número por
     * criação. O número continua estável (nasce da ordem de criação e nunca
     * renumera); quem quiser ver por data escolhe no controle.
     */
    public const ORDENS = [
        'num_desc'  => 'Nº decrescente (maior → 1)',
        'num_asc'   => 'Nº crescente (1 → maior)',
        'data_desc' => 'Data do jogo (mais recente)',
        'data_asc'  => 'Data do jogo (mais antiga)',
    ];

    /** Ordem usada quando nenhuma (ou uma inválida) é pedida. */
    public const ORDEM_PADRAO = 'num_desc';

    /** Normaliza a ordem vinda da URL; valor inválido vira o padrão. */
    public static function ordemValida(?string $ordem): string
    {
        return array_key_exists((string) $ordem, self::ORDENS) ? $ordem : self::ORDEM_PADRAO;
    }

    /**
     * Ordena a lista de reservas conforme a escolha do usuário.
     *
     * O número #N é o posto por `id` (ver numerosNaArena), então ordenar por
     * `id` faz a coluna Nº aparecer em sequência.
     */
    public function scopeOrdenado($query, ?string $ordem)
    {
        return match (self::ordemValida($ordem)) {
            'num_asc'   => $query->orderBy('id'),
            'data_desc' => $query->orderByDesc('date')->orderByDesc('start_time'),
            'data_asc'  => $query->orderBy('date')->orderBy('start_time'),
            default     => $query->orderByDesc('id'), // num_desc
        };
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }


    /**
     * Confirmada, não paga e a forma é dinheiro (paga na arena ao usar).
     */
    public function pagaNaArena(): bool
    {
        return $this->status === 'confirmed'
            && ! $this->isPaga()
            && $this->paymentMethod?->type === 'cash';
    }

    public function cancelledBy()
    {
        // withTrashed: quem cancelou continua identificado mesmo após a conta
        // dele ser excluída.
        return $this->belongsTo(User::class, 'cancelled_by')->withTrashed();
    }

    /**
     * A reserva já tem um pagamento confirmado (status 'paid')?
     * Usa a relação já carregada quando disponível (evita N+1 nas listagens).
     */
    public function isPaga(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->contains(fn ($p) => $p->status === 'paid');
        }

        return $this->payments()->where('status', 'paid')->exists();
    }

    /**
     * Situação de pagamento da reserva:
     * - 'pago'     : já tem pagamento confirmado;
     * - 'atrasado' : não pago e o horário já terminou;
     * - 'a_pagar'  : não pago e ainda vai acontecer;
     * - null       : não se aplica (pendente ou cancelada — ainda não é uma
     *                reserva que vai acontecer).
     */
    /**
     * Reservas em aberto: já aconteceram e não foram pagas.
     *
     * É a versão em consulta de `situacaoPagamento() === 'atrasado'`, para quem
     * precisa perguntar isso de muitas reservas de uma vez (a exclusão de conta
     * do cliente e a lista de clientes do admin) sem carregar cada uma.
     *
     * "Não paga" é a AUSÊNCIA de pagamento confirmado, e não a presença de um
     * pagamento pendente: quem nunca pagou não tem linha nenhuma em `payments`.
     * Era essa diferença que deixava uma conta devendo ser excluída.
     */
    public function scopeEmAberto($query)
    {
        return $query
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereRaw('TIMESTAMP(`date`, `end_time`) < ?', [now()])
            ->whereDoesntHave('payments', fn ($pagamento) => $pagamento->where('status', 'paid'));
    }

    public function situacaoPagamento(): ?string
    {
        // Só confirmadas/realizadas têm situação de pagamento.
        if (! in_array($this->status, ['confirmed', 'completed'])) {
            return null;
        }

        if ($this->isPaga()) {
            return 'pago';
        }

        $fim = Carbon::parse($this->date->toDateString() . ' ' . $this->end_time);

        return now()->greaterThan($fim) ? 'atrasado' : 'a_pagar';
    }

    /**
     * A reserva está "em andamento": confirmada e o horário atual está entre o
     * início e o fim (já começou mas ainda não terminou).
     */
    public function estaEmAndamento(): bool
    {
        if ($this->status !== 'confirmed') {
            return false;
        }

        $inicio = Carbon::parse($this->date->toDateString() . ' ' . $this->start_time);
        $fim = Carbon::parse($this->date->toDateString() . ' ' . $this->end_time);

        return now()->greaterThanOrEqualTo($inicio) && now()->lessThan($fim);
    }

    /**
     * O cliente pode editar somente enquanto o cancelamento ainda seria GRÁTIS
     * — ou seja, usando a MESMA janela que a arena configurou para a taxa de
     * cancelamento. Dentro da janela de taxa (ou já iniciada), a edição fica
     * bloqueada. Se a arena não cobra taxa, pode editar até o início.
     */
    public function podeSerEditadaPeloCliente(): bool
    {
        return $this->regraCancelamentoCliente() === 'livre';
    }

    /**
     * Texto da taxa numa reserva CANCELADA (para o histórico):
     * "Com taxa de R$ X" ou "Sem taxa". null se não estiver cancelada.
     */
    public function taxaCancelamentoDescricao(): ?string
    {
        if ($this->status !== 'cancelled') {
            return null;
        }

        $valor = (float) $this->cancellation_fee_amount;

        return $valor > 0
            ? 'Com taxa de R$ ' . number_format($valor, 2, ',', '.')
            : 'Sem taxa';
    }

    /**
     * Valor da taxa de cancelamento desta reserva, conforme a config da arena
     * (fixo em R$ ou percentual sobre o valor da reserva). 0 se a arena não cobra.
     */
    public function valorTaxaCancelamento(): float
    {
        $arena = $this->court?->arena;

        return $arena ? $arena->taxaCancelamentoPara((float) $this->total_amount) : 0.0;
    }

    /**
     * Regra de cancelamento pelo CLIENTE, usando a config da arena:
     * - pendente: pode cancelar sempre, sem taxa;
     * - confirmada: depende da arena —
     *     • não cobra taxa -> 'livre';
     *     • cobra no modo 'sempre' -> 'taxa';
     *     • cobra no modo 'janela' -> 'livre' se faltar mais de X horas, senão 'taxa';
     * - já começou/passada/cancelada/concluída: não pode (null).
     *
     * Retorna 'livre' | 'taxa' | null (null = não pode cancelar).
     */
    public function regraCancelamentoCliente(): ?string
    {
        $inicio = Carbon::parse($this->date->toDateString() . ' ' . $this->start_time);

        if (now()->greaterThanOrEqualTo($inicio)) {
            return null;
        }

        if ($this->status === 'pending') {
            return 'livre';
        }

        if ($this->status !== 'confirmed') {
            return null;
        }

        $arena = $this->court?->arena;

        // Arena não cobra taxa (ou valor zerado) -> sempre livre.
        if (! $arena || ! $arena->charges_cancellation_fee || $arena->taxaCancelamentoPara((float) $this->total_amount) <= 0) {
            return 'livre';
        }

        // Cobra sempre que estiver confirmada.
        if ($arena->cancellation_fee_mode === 'always') {
            return 'taxa';
        }

        // Modo janela: grátis se faltar mais de X horas para o início.
        $horas = (int) ($arena->cancellation_fee_window_hours ?? 0);
        $limite = $inicio->copy()->subHours($horas);

        return now()->lessThan($limite) ? 'livre' : 'taxa';
    }

    /**
     * Prazo que o dono/atendente tem para confirmar/cancelar a reserva.
     * - criada com mais de 10 min até o início: 10 min;
     * - criada com 10 min ou menos: metade do tempo que faltava.
     */
    public function prazoConfirmacao(): Carbon
    {
        $inicio = Carbon::parse($this->date->toDateString() . ' ' . $this->start_time);
        $criado = $this->created_at ?? now();
        $minsAteInicio = $criado->diffInMinutes($inicio, false); // negativo se já passou

        if ($minsAteInicio <= 10) {
            return $criado->copy()->addSeconds(max(0, (int) ($minsAteInicio * 60 / 2)));
        }

        return $criado->copy()->addMinutes(10);
    }

    /**
     * Está pendente e o prazo de confirmação já passou.
     */
    public function deveAutoConfirmar(): bool
    {
        return $this->status === 'pending'
            && now()->greaterThanOrEqualTo($this->prazoConfirmacao());
    }

    /**
     * Confirma automaticamente as reservas pendentes cujo prazo expirou.
     * Chamada de forma "preguiçosa" ao abrir as telas de agendamentos.
     */
    public static function autoConfirmarExpiradas(?array $courtIds = null): void
    {
        $query = static::where('status', 'pending');

        if ($courtIds !== null) {
            $query->whereIn('court_id', $courtIds);
        }

        foreach ($query->get() as $booking) {
            if ($booking->deveAutoConfirmar()) {
                $booking->update(['status' => 'confirmed']);
                $booking->notificarClienteConfirmada();
            }
        }
    }

    /**
     * Marca como realizadas (completed) as reservas confirmadas cujo horário
     * de término já passou.
     *
     * O filtro do horário é feito no SQL (antes carregava TODAS as confirmadas,
     * inclusive as futuras, para descartar no PHP) e os pagamentos vêm em eager
     * loading, porque isPaga() consultaria o banco uma vez por reserva (N+1).
     */
    public static function autoCompletarRealizadas(?array $courtIds = null): void
    {
        $query = static::where('status', 'confirmed')
            // TIMESTAMP(date, end_time) junta data + hora de término (MySQL).
            ->whereRaw('TIMESTAMP(`date`, `end_time`) < ?', [now()])
            ->with('payments');

        if ($courtIds !== null) {
            $query->whereIn('court_id', $courtIds);
        }

        foreach ($query->get() as $booking) {
            $booking->update(['status' => 'completed']);

            if (! $booking->isPaga()) {
                $booking->notificarClienteNaoPaga();
                // A arena também precisa saber: vira dinheiro a receber.
                $booking->notificarStaffNaoPaga();
            }
        }
    }

    /**
     * Descrição curta da reserva para mensagens/notificações.
     */
    public function descricaoCurta(): string
    {
        return ($this->court->name ?? 'Quadra') . ' — '
            . $this->date->format('d/m/Y') . ' '
            . substr($this->start_time, 0, 5) . '–' . substr($this->end_time, 0, 5);
    }

    public function notificarClienteConfirmada(?int $sentBy = null): void
    {
        UserNotification::paraReserva(
            $this,
            'Reserva confirmada',
            'Sua reserva foi confirmada: ' . $this->descricaoCurta() . '.',
            $sentBy
        );
    }

    /**
     * Cancela VÁRIAS reservas de uma vez E AVISA cada cliente.
     *
     * Existe porque os cancelamentos em massa (desativar/excluir arena ou
     * quadra, mudar horário de funcionamento, excluir empresa) faziam
     * `->update(['status' => 'cancelled'])` direto no banco. Aquilo pula o
     * model e, com ele, o aviso: o cliente tinha o jogo cancelado e não ficava
     * sabendo — nem recebia o motivo que a arena foi obrigada a escrever.
     *
     * Reserva JÁ PAGA é REEMBOLSADA integralmente (sem taxa): quem cancelou foi
     * a arena, não o cliente. É a mesma regra do cancelamento individual — sem
     * isto, excluir uma arena com reservas pagas deixava os clientes avisados
     * mas sem o dinheiro de volta.
     *
     * Reserva presencial (sem cliente cadastrado) simplesmente não gera aviso:
     * UserNotification::paraReserva já trata esse caso.
     *
     * @param  iterable<int, self>  $reservas  reservas ATIVAS a cancelar
     * @return int  quantas foram canceladas
     */
    public static function cancelarEmLote(iterable $reservas, string $motivo, ?int $sentBy = null): int
    {
        $reservas = $reservas instanceof \Illuminate\Support\Collection
            ? $reservas
            : collect($reservas);

        if ($reservas->isEmpty()) {
            return 0;
        }

        // Carrega de uma vez as relações que o laço usaria por reserva. Sem
        // isto, o reembolso e as notificações refaziam as mesmas consultas
        // (quadra, arena, pagamentos, cliente) a cada volta — o custo crescia
        // reta com o número de reservas e travava a exclusão de uma arena cheia.
        $reservas->load('court.arena', 'payments', 'client.user');

        // O status vai num UPDATE único, em vez de um por reserva. Os modelos
        // em memória são acertados na mão para o resto do laço (reembolso,
        // avisos) enxergar o novo estado sem reconsultar.
        $agora = now();
        static::whereIn('id', $reservas->modelKeys())->update([
            'status' => 'cancelled',
            'cancelled_by' => $sentBy,
            'cancelled_at' => $agora,
            'cancellation_reason' => $motivo,
        ]);

        foreach ($reservas as $booking) {
            $booking->forceFill([
                'status' => 'cancelled',
                'cancelled_by' => $sentBy,
                'cancelled_at' => $agora,
                'cancellation_reason' => $motivo,
            ])->syncOriginal();

            // Devolve o dinheiro se estava paga. `reembolsar` retorna null
            // quando não havia pagamento, então não precisa checar antes.
            $pagamento = PaymentService::reembolsar($booking, 0.0, $sentBy);

            $booking->notificarClienteCancelada($motivo, $sentBy);

            if ($pagamento) {
                $booking->notificarClienteReembolso((float) $pagamento->refund_amount, 0.0, $sentBy);
            }
        }

        return $reservas->count();
    }

    public function notificarClienteCancelada(?string $motivo = null, ?int $sentBy = null): void
    {
        $texto = 'Sua reserva foi cancelada pela arena: ' . $this->descricaoCurta() . '.';
        if ($motivo) {
            $texto .= ' Motivo: ' . $motivo;
        }

        UserNotification::paraReserva($this, 'Reserva cancelada', $texto, $sentBy);
    }

    public function notificarClienteReagendada(?int $sentBy = null): void
    {
        UserNotification::paraReserva(
            $this,
            'Reserva reagendada',
            'Sua reserva foi reagendada pela arena para: ' . $this->descricaoCurta() . '.',
            $sentBy
        );
    }

    public function notificarClienteNaoPaga(?int $sentBy = null): void
    {
        UserNotification::paraReserva(
            $this,
            'Reserva não paga',
            'A sua reserva #' . $this->numeroDoCliente()
                . ' na ' . ($this->court->arena->name ?? 'arena')
                . ' (' . $this->descricaoCurta() . ') foi realizada, '
                . 'mas ficou sem pagamento. '
                . 'Você ainda pode pagá-la na área "Pagamentos pendentes".',
            $sentBy
        );
    }

    /**
     * Avisa o cliente do reembolso quando a reserva JÁ PAGA é cancelada
     * (devolvido o valor pago menos a taxa retida, se houver).
     */
    public function notificarClienteReembolso(float $reembolso, float $taxa = 0, ?int $sentBy = null): void
    {
        $texto = 'Sua reserva foi cancelada: ' . $this->descricaoCurta() . '. ';
        if ($taxa > 0) {
            $texto .= 'Foi retida a taxa de cancelamento de R$ '
                . number_format($taxa, 2, ',', '.') . '. ';
        }
        $texto .= 'Você foi reembolsado em R$ ' . number_format($reembolso, 2, ',', '.') . '. ';

        // Deixa claro COMO o valor volta (estorno pix/cartão x devolução em dinheiro).
        $this->loadMissing('payments.paymentMethod');
        $pago = $this->payments->first(fn ($p) => $p->refunded_at !== null)
            ?? $this->payments->firstWhere('status', 'paid');
        if ($pago) {
            $texto .= $pago->comoReembolsar();
        }

        UserNotification::paraReserva($this, 'Reserva cancelada — reembolso', trim($texto), $sentBy);
    }

    /**
     * Avisa o staff da arena (dono + funcionários ativos, gerentes e atendentes)
     * de que o cliente criou uma reserva e ela está aguardando confirmação.
     */
    /**
     * Avisa o staff de que o cliente PAGOU a reserva pelo site.
     *
     * Sem isto, o dinheiro entrava sem ninguém da arena saber. Importa mais
     * quando o caixa está fechado: o pagamento fica pendente de lançamento e
     * alguém precisa lançá-lo na próxima abertura (ver CashRegisterController).
     */
    public function notificarStaffPagamentoRecebido(float $valor): void
    {
        $this->loadMissing('court.arena', 'client.user');
        $arena = $this->court?->arena;

        if (! $arena) {
            return;
        }

        UserNotification::paraStaffDaArena(
            $arena,
            'Pagamento recebido',
            // {cliente} no lugar do nome: resolvido na exibição pela reserva
            // passada abaixo, para não congelar o nome de quem talvez encerre
            // a conta depois. Ver UserNotification::corpoResolvido().
            UserNotification::CLIENTE . ' pagou R$ ' . number_format($valor, 2, ',', '.')
                . ' pelo site — reserva ' . $this->descricaoCurta() . '.'
                . ' Se o caixa estiver fechado, o lançamento fica pendente para a próxima abertura.',
            $this
        );
    }

    /**
     * Avisa o staff de que a reserva foi realizada mas ficou SEM pagamento.
     *
     * É dinheiro a receber: a reserva entra na lista de "a receber" do caixa, e
     * antes disso só o cliente era avisado — a arena não ficava sabendo.
     */
    public function notificarStaffNaoPaga(): void
    {
        $this->loadMissing('court.arena', 'client.user');
        $arena = $this->court?->arena;

        if (! $arena) {
            return;
        }

        UserNotification::paraStaffDaArena(
            $arena,
            'Reserva concluída sem pagamento',
            'A reserva ' . $this->descricaoCurta() . ' (cliente: ' . UserNotification::CLIENTE . ')'
                . ' foi realizada e ficou sem pagamento.',
            $this
        );
    }

    public function notificarStaffNovaReserva(): void
    {
        $this->loadMissing('court.arena', 'client.user');
        $arena = $this->court?->arena;

        if (! $arena) {
            return;
        }

        UserNotification::paraStaffDaArena(
            $arena,
            'Nova reserva pendente',
            'Nova reserva aguardando confirmação: ' . $this->descricaoCurta()
                . ' (cliente: ' . UserNotification::CLIENTE . ').',
            $this
        );
    }

    /**
     * Avisa o staff da arena de que o PRÓPRIO cliente cancelou a reserva.
     */
    public function notificarStaffCanceladaPeloCliente(?string $motivo = null, float $taxaPaga = 0): void
    {
        $this->loadMissing('court.arena', 'client.user');
        $arena = $this->court?->arena;

        if (! $arena) {
            return;
        }

        $texto = 'O cliente ' . UserNotification::CLIENTE . ' cancelou a reserva: '
            . $this->descricaoCurta() . '.';
        if ($motivo) {
            $texto .= ' Motivo: ' . $motivo;
        }

        // A taxa entra AQUI em vez de virar um segundo aviso: o cancelamento e o
        // pagamento da taxa são o mesmo ato do cliente, e dois avisos seguidos
        // para a mesma ação viram ruído no sino.
        if ($taxaPaga > 0) {
            $texto .= ' Taxa de cancelamento paga: R$ ' . number_format($taxaPaga, 2, ',', '.')
                . ' (lançada no caixa, ou pendente se ele estiver fechado).';
        }

        UserNotification::paraStaffDaArena($arena, 'Reserva cancelada pelo cliente', $texto, $this);
    }
}
