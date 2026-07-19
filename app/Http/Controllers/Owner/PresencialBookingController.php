<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Court;
use App\Services\CourtScheduleService;
use App\Support\ArenaAtual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Reserva registrada no balcão da arena, para o cliente que aparece na hora.
 *
 * Obedece exatamente as mesmas regras da reserva feita pelo cliente no site:
 * o horário é validado contra a grade oficial da arena, a criação acontece
 * dentro de uma transação com trava na quadra, e o índice único do banco
 * impede duas reservas no mesmo horário.
 *
 * A diferença é que ela nasce CONFIRMADA (quem registra é a própria arena),
 * grava quem a registrou (`created_by`) e pode ter um cliente sem cadastro.
 */
class PresencialBookingController extends Controller
{
    /**
     * A arena que o dono está gerenciando agora.
     */
    private function arenaAtual(): Arena
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        return ArenaAtual::obter();
    }

    /**
     * Recusa registrar reserva quando a arena está DESATIVADA.
     *
     * Arena inativa não aparece para o cliente e não pode abrir caixa (ver
     * CashRegisterController::open) — então também não pode receber reserva nova
     * pelo balcão. Sem esta guarda, a arena ficava "fora do ar" para o cliente
     * enquanto a equipe seguia vendendo horário nela: desativar a arena NÃO
     * desativa as quadras, então elas continuavam disponíveis aqui dentro.
     *
     * O bloqueio é da AÇÃO, não do cargo: vale para dono, gerente e atendente.
     * O acesso ao painel (consultar histórico, reativar a arena) segue liberado.
     */
    private function bloqueioArenaInativa(Arena $arena)
    {
        if ($arena->active) {
            return null;
        }

        return redirect()->route('owners.dashboard')
            ->with('aviso', 'A arena "'.$arena->name.'" está inativa. Reative-a para registrar reservas.');
    }

    /**
     * Formulário: escolher quadra, dia, horário e quem vai jogar.
     */
    public function create(Request $request)
    {
        $arena = $this->arenaAtual();

        if ($bloqueio = $this->bloqueioArenaInativa($arena)) {
            return $bloqueio;
        }

        $arena->load('businessHours');

        $quadras = $arena->courts()->where('active', true)->orderBy('name')->get();

        if ($quadras->isEmpty()) {
            return redirect()->route('owners.dashboard')
                ->with('aviso', 'Cadastre ao menos uma quadra ativa antes de registrar reservas.');
        }

        $quadra = $quadras->firstWhere('id', (int) $request->query('court_id')) ?? $quadras->first();
        $data = $request->query('date', now()->toDateString());

        $aberto = $arena->businessHours->where('day_of_week', \Carbon\Carbon::parse($data)->dayOfWeek)->isNotEmpty();

        // incluirEmCurso: no balcão, o bloco que já começou ainda pode ser vendido.
        $slots = $aberto
            ? CourtScheduleService::slotsDoDia($quadra, $arena, $data, null, true)
            : collect();

        // Clientes cadastrados, para o dono vincular a reserva a quem já tem conta.
        $clientes = Client::whereHas('user')
            ->with('user')
            ->get()
            ->sortBy(fn ($c) => $c->user->name)
            ->values();

        return view('bookings.presencial', compact('arena', 'quadras', 'quadra', 'data', 'aberto', 'slots', 'clientes'));
    }

    public function store(Request $request)
    {
        $arena = $this->arenaAtual();

        // Também no POST: sem isto, bastaria enviar o formulário direto (ou tê-lo
        // aberto antes de a arena ser desativada) para furar o bloqueio do GET.
        if ($bloqueio = $this->bloqueioArenaInativa($arena)) {
            return $bloqueio;
        }

        $arena->load('businessHours');

        $dados = $request->validate([
            'court_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'horarios' => ['required', 'array', 'min:1'],
            'horarios.*' => ['required', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
            'tipo_cliente' => ['required', Rule::in(['cadastrado', 'avulso'])],

            'client_id' => ['required_if:tipo_cliente,cadastrado', 'nullable', 'integer', 'exists:clients,id'],

            'guest_name' => ['required_if:tipo_cliente,avulso', 'nullable', 'string', 'max:120'],
            'guest_phone' => ['required_if:tipo_cliente,avulso', 'nullable', 'string', 'max:20'],
            'guest_email' => ['nullable', 'email', 'max:150'],

            // Anotação do balcão sobre a reserva. Vale para os dois tipos de
            // cliente e é apagada junto com os dados pessoais se o cliente
            // encerrar a conta (ver Client::desligarReservasAnonimizando).
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'horarios.required' => 'Escolha ao menos um horário.',
            'horarios.min' => 'Escolha ao menos um horário.',
            'client_id.required_if' => 'Escolha o cliente cadastrado.',
            'guest_name.required_if' => 'Informe o nome de quem vai jogar.',
            'guest_phone.required_if' => 'Informe o telefone de contato.',
        ]);

        // A quadra tem de ser desta arena e estar ativa.
        $quadra = $arena->courts()->where('active', true)->find($dados['court_id']);
        abort_unless($quadra, 404, 'Quadra não encontrada nesta arena.');

        // O mesmo bloco pode vir repetido no POST — cada bloco vale uma reserva só.
        $horarios = array_values(array_unique($dados['horarios']));
        $avulso = $dados['tipo_cliente'] === 'avulso';

        try {
            $reservas = DB::transaction(function () use ($arena, $quadra, $dados, $horarios, $avulso) {
                // Serializa pedidos concorrentes para a MESMA quadra. Sem isto, o
                // dono e um cliente no site poderiam reservar o mesmo horário.
                Court::whereKey($quadra->id)->lockForUpdate()->first();

                // Grade recalculada DENTRO do lock. Já cobre horário de
                // funcionamento, blocos de 1h, ocupados e horários encerrados.
                $slots = CourtScheduleService::slotsDoDia($quadra, $arena, $dados['date'], null, true);

                // 1) Valida TODOS os horários antes de criar qualquer reserva
                //    (tudo ou nada), igual ao fluxo do cliente.
                foreach ($horarios as $horario) {
                    [$inicio, $fim] = explode('-', $horario);

                    $livre = $slots->contains(fn ($slot) => ! $slot['ocupado']
                        && $slot['start'] === $inicio
                        && $slot['end'] === $fim
                    );

                    if (! $livre) {
                        throw new \RuntimeException('slot-indisponivel');
                    }
                }

                // 2) Só então cria — uma reserva confirmada por horário.
                $criadas = [];
                foreach ($horarios as $horario) {
                    [$inicio, $fim] = explode('-', $horario);

                    $criadas[] = Booking::create([
                        'court_id' => $quadra->id,
                        'client_id' => $avulso ? null : $dados['client_id'],
                        'guest_name' => $avulso ? $dados['guest_name'] : null,
                        'guest_phone' => $avulso ? $dados['guest_phone'] : null,
                        'guest_email' => $avulso ? ($dados['guest_email'] ?? null) : null,

                        // Quem registrou (dono, gerente ou atendente). É a fonte única:
                        // o tipo é derivado do usuário na tela de detalhes.
                        'created_by' => auth()->id(),

                        // Observação do balcão (vazia vira null, não string vazia).
                        'notes' => trim((string) ($dados['notes'] ?? '')) ?: null,

                        'origin' => Booking::ORIGEM_PRESENCIAL,
                        'date' => $dados['date'],
                        'start_time' => $inicio,
                        'end_time' => $fim,
                        'total_amount' => $quadra->hourly_rate,

                        // Nasce confirmada: quem registra é a própria arena.
                        'status' => 'confirmed',
                    ]);
                }

                return $criadas;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'slot-indisponivel') {
                throw $e;
            }

            return back()
                ->withErrors(['horarios' => 'Um dos horários selecionados não está mais disponível. Escolha outro.'])
                ->withInput();
        }

        // Cliente cadastrado é avisado no sino e por e-mail (uma vez por reserva).
        // Para o cliente avulso a notificação não tem destinatário e é ignorada.
        foreach ($reservas as $reserva) {
            $reserva->notificarClienteConfirmada(auth()->id());
        }

        $n = count($reservas);

        // Volta para a própria tela de registro (com a mesma quadra e dia), para
        // o atendente confirmar o sucesso ali e já lançar a próxima reserva.
        return redirect()->route('bookings.presencial.create', [
            'court_id' => $quadra->id,
            'date' => $dados['date'],
        ])->with('msg', $n === 1
            ? "Reserva registrada para {$reservas[0]->nomeCliente()}."
            : "{$n} reservas registradas para {$reservas[0]->nomeCliente()}.");
    }
}
