<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Owner;
use App\Models\PaymentMethod;
use App\Models\User;
class ArenaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $arenas = auth()->user()->arenas;
        return view('arenas.index', compact('arenas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('arenas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem cadastrar arenas.');
        }

        $request->merge([
            'nome' => self::normalizarTexto($request->input('nome')),
            'email_contato' => self::normalizarEmail($request->input('email_contato')),
            'quadras' => self::normalizarNomesQuadras($request->input('quadras', [])),
        ]);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120', function ($attribute, $value, $fail) {
                $chave = self::chaveComparacao($value);
                if (Arena::whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [$chave])->exists()) {
                    $fail('Já existe uma arena com esse nome.');
                }
            }],
            'rua' => ['required', 'string', 'max:120'],
            'bairro' => ['required', 'string', 'max:100'],
            'numero' => ['required', 'string', 'max:15'],
            'descricao' => ['nullable', 'string'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email_contato' => ['nullable', 'email', 'max:150', function ($attribute, $value, $fail) use ($owner) {
                if (self::emailDeArenaEmUsoPorOutroDono($value, $owner->id)) {
                    $fail('Este e-mail já está sendo usado por uma arena de outro proprietário.');
                } elseif (self::emailPertenceAOutroUsuario($value, auth()->id())) {
                    $fail('Este e-mail pertence à conta de outra pessoa. Use um e-mail que não seja de outro usuário.');
                }
            }],
            'horarios' => ['required', 'array', function ($attribute, $value, $fail) {
                $algumDia = collect($value)->contains(fn ($dia) => ! empty($dia['aberto']));
                if (! $algumDia) {
                    $fail('Marque ao menos um dia de funcionamento.');
                    return;
                }
                if ($erro = self::erroNosPeriodos($value)) {
                    $fail($erro);
                }
            }],
            'horarios.*.aberto' => ['nullable', 'boolean'],
            'horarios.*.p1_abre' => ['required_with:horarios.*.aberto', 'date_format:H:i'],
            'horarios.*.p1_fecha' => ['required_with:horarios.*.aberto', 'date_format:H:i'],
            'horarios.*.p2_abre' => ['nullable', 'date_format:H:i'],
            'horarios.*.p2_fecha' => ['nullable', 'date_format:H:i'],
            'pagamentos' => ['required', 'array', 'min:1'],
            'pagamentos.*' => ['integer', 'exists:payment_methods,id'],
            'quadras' => ['required', 'array', 'min:1', function ($attribute, $value, $fail) {
                if (self::temNomesDeQuadraDuplicados($value)) {
                    $fail('Há quadras com nomes equivalentes (ignorando espaços e maiúsculas).');
                }
            }],
            'quadras.*.nome' => ['required', 'string', 'max:80'],
            'quadras.*.descricao' => ['nullable', 'string'],
            'quadras.*.valor_hora' => ['required', 'numeric', 'min:0'],
            'quadras.*.ativa' => ['nullable', 'boolean'],
            'quadras.*.esportes' => ['required', 'array', 'min:1'],
            'quadras.*.esportes.*' => [Rule::in(array_keys(Court::SPORTS))],
        ], [
            'horarios.required' => 'Marque ao menos um dia de funcionamento.',
            'horarios.*.p1_abre.required_with' => 'Informe o horário de abertura do dia marcado.',
            'horarios.*.p1_fecha.required_with' => 'Informe o horário de fechamento do dia marcado.',
            'pagamentos.required' => 'Selecione ao menos uma forma de pagamento.',
            'pagamentos.min' => 'Selecione ao menos uma forma de pagamento.',
            'quadras.required' => 'Cadastre ao menos uma quadra.',
            'quadras.min' => 'Cadastre ao menos uma quadra.',
            'quadras.*.nome.required' => 'Informe o nome da quadra.',
            'quadras.*.valor_hora.required' => 'Informe o valor por hora da quadra.',
            'quadras.*.esportes.required' => 'Selecione ao menos um esporte por quadra.',
            'quadras.*.esportes.min' => 'Selecione ao menos um esporte por quadra.',
        ]);

        $arena = Arena::create([
            'owner_id' => $owner->id,
            'name' => $validated['nome'],
            'address_rua' => $validated['rua'],
            'address_bairro' => $validated['bairro'],
            'address_numero' => $validated['numero'],
            'description' => $validated['descricao'] ?? null,
            'phone' => $validated['telefone'] ?? null,
            'contact_email' => $validated['email_contato'] ?? null,
        ]);

        $this->salvarHorarios($arena, $request->input('horarios', []));

        self::salvarQuadras($arena, $request->input('quadras', []));

        $arena->paymentMethods()->sync($request->input('pagamentos', []));

        return redirect()->route('owners.dashboard');
    }
    
    public function show($id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        // Só o dono vê os dados da própria arena.
        $arena = $owner
            ? $owner->arenas()
                ->with(['courts.sports', 'businessHours', 'paymentMethods'])
                ->find($id)
            : null;

        if (! $arena) {
            abort(404);
        }

        // Todas as formas de pagamento disponíveis (para o formulário de edição).
        $todasFormasPagamento = PaymentMethod::where('active', true)->get();

        // Horários atuais no formato do formulário (por dia, 2 períodos).
        $horariosAtuais = [];
        foreach ($arena->businessHours->groupBy('day_of_week') as $dia => $intervalos) {
            $intervalos = $intervalos->sortBy('opens_at')->values();
            $horariosAtuais[$dia] = [
                'aberto' => 1,
                'p1_abre' => substr($intervalos[0]->opens_at, 0, 5),
                'p1_fecha' => substr($intervalos[0]->closes_at, 0, 5),
                'p2_abre' => isset($intervalos[1]) ? substr($intervalos[1]->opens_at, 0, 5) : '',
                'p2_fecha' => isset($intervalos[1]) ? substr($intervalos[1]->closes_at, 0, 5) : '',
            ];
        }

        return view('arenas.show', compact('arena', 'todasFormasPagamento', 'horariosAtuais'));
    }

    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

    }

    /**
     * Atualiza só as formas de pagamento aceitas pela arena.
     */
    public function updatePayments(Request $request, string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        $validated = $request->validate([
            'pagamentos' => ['required', 'array', 'min:1'],
            'pagamentos.*' => ['integer', 'exists:payment_methods,id'],
        ], [
            'pagamentos.required' => 'Selecione ao menos uma forma de pagamento.',
            'pagamentos.min' => 'Selecione ao menos uma forma de pagamento.',
        ]);

        $arena->paymentMethods()->sync($validated['pagamentos']);

        return redirect()->route('arenas.show', $arena->id)
            ->with('msg', 'Formas de pagamento atualizadas.');
    }

    /**
     * Atualiza só o nome da arena (mesma regra de unicidade do cadastro:
     * ignora espaços e maiúsculas, e desconsidera a própria arena).
     */
    public function updateName(Request $request, string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        $request->merge([
            'nome' => self::normalizarTexto($request->input('nome')),
        ]);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120', function ($attribute, $value, $fail) use ($arena) {
                $chave = self::chaveComparacao($value);
                if (Arena::whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [$chave])
                        ->where('id', '!=', $arena->id)
                        ->exists()) {
                    $fail('Já existe uma arena com esse nome.');
                }
            }],
        ]);

        $arena->update(['name' => $validated['nome']]);

        return redirect()->route('arenas.show', $arena->id)
            ->with('msg', 'Nome da arena atualizado.');
    }

    /**
     * Ativa/desativa a arena. Reativar é direto. Desativar só é direto se não
     * houver reservas futuras pendentes/confirmadas; se houver, pede confirmação
     * com motivo (mesma ideia da troca de horários).
     */
    public function toggleActive(string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        // Reativar.
        if (! $arena->active) {
            $arena->update(['active' => true]);

            return redirect()->route('arenas.show', $arena->id)
                ->with('msg', 'Arena reativada.');
        }

        // Desativar: verifica reservas que seriam canceladas.
        $afetados = self::agendamentosAtivosDaArena($arena);

        if ($afetados->isEmpty()) {
            $arena->update(['active' => false]);

            return redirect()->route('arenas.show', $arena->id)
                ->with('msg', 'Arena desativada.');
        }

        return view('arenas.deactivate-confirm', compact('arena', 'afetados'));
    }

    /**
     * Confirma a desativação da arena cancelando as reservas afetadas.
     * O motivo é o mesmo para todas.
     */
    public function confirmDeactivate(Request $request, string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        $motivo = trim((string) $request->input('motivo'));

        if ($motivo === '') {
            return view('arenas.deactivate-confirm', [
                'arena' => $arena,
                'afetados' => self::agendamentosAtivosDaArena($arena),
                'erroMotivo' => 'Informe o motivo do cancelamento.',
                'motivo' => $motivo,
            ]);
        }

        DB::transaction(function () use ($arena, $motivo) {
            foreach (self::agendamentosAtivosDaArena($arena) as $booking) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => $motivo,
                ]);
            }

            $arena->update(['active' => false]);
        });

        return redirect()->route('arenas.show', $arena->id)
            ->with('msg', 'Arena desativada e reservas afetadas canceladas.');
    }

    /**
     * Reservas futuras (pendentes/confirmadas) de todas as quadras da arena.
     */
    public static function agendamentosAtivosDaArena(Arena $arena)
    {
        return Booking::with(['court', 'client.user'])
            ->whereIn('court_id', $arena->courts()->select('id'))
            ->whereDate('date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('date')->orderBy('start_time')
            ->get();
    }

    /**
     * Atualiza o endereço e o contato da arena.
     */
    public function updateContact(Request $request, string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        $request->merge([
            'email_contato' => self::normalizarEmail($request->input('email_contato')),
        ]);

        $validated = $request->validate([
            'rua' => ['required', 'string', 'max:120'],
            'bairro' => ['required', 'string', 'max:100'],
            'numero' => ['required', 'string', 'max:15'],
            'telefone' => ['required', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:300'],
            'email_contato' => ['required', 'email', 'max:150', function ($attribute, $value, $fail) use ($owner) {
                if (self::emailDeArenaEmUsoPorOutroDono($value, $owner->id)) {
                    $fail('Este e-mail já está sendo usado por uma arena de outro proprietário.');
                } elseif (self::emailPertenceAOutroUsuario($value, auth()->id())) {
                    $fail('Este e-mail pertence à conta de outra pessoa. Use um e-mail que não seja de outro usuário.');
                }
            }],
        ], [
            'telefone.required' => 'Informe o telefone.',
            'email_contato.required' => 'Informe o e-mail de contato.',
        ]);

        $arena->update([
            'address_rua' => $validated['rua'],
            'address_bairro' => $validated['bairro'],
            'address_numero' => $validated['numero'],
            'phone' => $validated['telefone'],
            'contact_email' => $validated['email_contato'],
            'description' => $validated['descricao'] ?? null,
        ]);

        return redirect()->route('arenas.show', $arena->id)
            ->with('msg', 'Endereço e contato atualizados.');
    }

    /**
     * Atualiza só os horários de funcionamento da arena (substitui todos).
     */
    public function updateBusinessHours(Request $request, string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        $request->validate([
            'horarios' => ['required', 'array', function ($attribute, $value, $fail) {
                $algumDia = collect($value)->contains(fn ($dia) => ! empty($dia['aberto']));
                if (! $algumDia) {
                    $fail('Marque ao menos um dia de funcionamento.');
                    return;
                }
                if ($erro = self::erroNosPeriodos($value)) {
                    $fail($erro);
                }
            }],
            'horarios.*.aberto' => ['nullable', 'boolean'],
            'horarios.*.p1_abre' => ['required_with:horarios.*.aberto', 'date_format:H:i'],
            'horarios.*.p1_fecha' => ['required_with:horarios.*.aberto', 'date_format:H:i'],
            'horarios.*.p2_abre' => ['nullable', 'date_format:H:i'],
            'horarios.*.p2_fecha' => ['nullable', 'date_format:H:i'],
        ], [
            'horarios.required' => 'Marque ao menos um dia de funcionamento.',
            'horarios.*.p1_abre.required_with' => 'Informe o horário de abertura do dia marcado.',
            'horarios.*.p1_fecha.required_with' => 'Informe o horário de fechamento do dia marcado.',
        ]);

        $horarios = $request->input('horarios', []);

        // Reservas futuras (todas as quadras) que ficariam fora do novo horário.
        $afetados = self::agendamentosForaDoHorario($arena, $horarios);

        // Se alguma reserva seria afetada, pede confirmação + motivo antes.
        if ($afetados->isNotEmpty()) {
            return view('arenas.hours-confirm', [
                'arena' => $arena,
                'afetados' => $afetados,
                'horarios' => $horarios,
            ]);
        }

        // Nenhuma afetada: substitui os horários direto.
        $arena->businessHours()->delete();
        $this->salvarHorarios($arena, $horarios);

        return redirect()->route('arenas.show', $arena->id)
            ->with('msg', 'Horários de funcionamento atualizados.');
    }

    /**
     * Confirma a troca de horários cancelando as reservas que ficaram fora.
     * O motivo é o mesmo para todas as reservas afetadas.
     */
    public function confirmBusinessHours(Request $request, string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        $horarios = $request->input('horarios', []);
        $motivo = trim((string) $request->input('motivo'));

        // Validação leve (os horários já vieram de um envio válido).
        $erros = [];
        $algumDia = collect($horarios)->contains(fn ($dia) => ! empty($dia['aberto']));
        if (! $algumDia) {
            $erros[] = 'Marque ao menos um dia de funcionamento.';
        } elseif ($erro = self::erroNosPeriodos($horarios)) {
            $erros[] = $erro;
        }
        if ($motivo === '') {
            $erros[] = 'Informe o motivo do cancelamento.';
        }

        if (! empty($erros)) {
            return view('arenas.hours-confirm', [
                'arena' => $arena,
                'afetados' => self::agendamentosForaDoHorario($arena, $horarios),
                'horarios' => $horarios,
                'erros' => $erros,
                'motivo' => $motivo,
            ]);
        }

        DB::transaction(function () use ($arena, $horarios, $motivo) {
            // Cancela as reservas que ficariam fora do novo horário.
            foreach (self::agendamentosForaDoHorario($arena, $horarios) as $booking) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => $motivo,
                ]);
            }

            // Substitui os horários.
            $arena->businessHours()->delete();
            self::salvarHorarios($arena, $horarios);
        });

        return redirect()->route('arenas.show', $arena->id)
            ->with('msg', 'Horários atualizados e reservas afetadas canceladas.');
    }

    /**
     * Diz se um intervalo [start, end] cabe em algum período aberto do dia.
     */
    public static function cabeNoHorario(int $weekday, string $start, string $end, array $horarios): bool
    {
        $info = $horarios[$weekday] ?? null;

        if (empty($info['aberto'])) {
            return false;
        }

        $s = substr($start, 0, 5);
        $e = substr($end, 0, 5);

        $periodos = [
            ['abre' => $info['p1_abre'] ?? null, 'fecha' => $info['p1_fecha'] ?? null],
            ['abre' => $info['p2_abre'] ?? null, 'fecha' => $info['p2_fecha'] ?? null],
        ];

        foreach ($periodos as $p) {
            if (empty($p['abre']) || empty($p['fecha'])) {
                continue;
            }
            if ($p['abre'] <= $s && $e <= $p['fecha']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reservas futuras (pendentes/confirmadas) de TODAS as quadras da arena
     * que NÃO cabem nos horários informados.
     */
    public static function agendamentosForaDoHorario(Arena $arena, array $horarios)
    {
        return Booking::with(['court', 'client.user'])
            ->whereIn('court_id', $arena->courts()->select('id'))
            ->whereDate('date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('date')->orderBy('start_time')
            ->get()
            ->filter(fn ($b) => ! self::cabeNoHorario(
                $b->date->dayOfWeek, $b->start_time, $b->end_time, $horarios
            ))
            ->values();
    }

    /**
     * Exclui a arena (soft delete: mantém o histórico no banco). Direto se não
     * houver reservas futuras; se houver, pede confirmação com motivo.
     */
    public function destroy(string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        $afetados = self::agendamentosAtivosDaArena($arena);

        if ($afetados->isEmpty()) {
            $this->excluirArena($arena);

            return redirect()->route('owners.dashboard')
                ->with('msg', 'Arena excluída.');
        }

        return view('arenas.delete-confirm', compact('arena', 'afetados'));
    }

    /**
     * Confirma a exclusão da arena cancelando as reservas afetadas.
     * O motivo é o mesmo para todas.
     */
    public function confirmDelete(Request $request, string $id)
    {
        $owner = Owner::where('user_id', auth()->id())->first();
        $arena = $owner ? $owner->arenas()->find($id) : null;

        if (! $arena) {
            abort(404);
        }

        $motivo = trim((string) $request->input('motivo'));

        if ($motivo === '') {
            return view('arenas.delete-confirm', [
                'arena' => $arena,
                'afetados' => self::agendamentosAtivosDaArena($arena),
                'erroMotivo' => 'Informe o motivo do cancelamento.',
                'motivo' => $motivo,
            ]);
        }

        DB::transaction(function () use ($arena, $motivo) {
            foreach (self::agendamentosAtivosDaArena($arena) as $booking) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => $motivo,
                ]);
            }

            $this->excluirArena($arena);
        });

        return redirect()->route('owners.dashboard')
            ->with('msg', 'Arena excluída e reservas afetadas canceladas.');
    }

    /**
     * Faz o soft delete da arena e limpa a seleção de sessão, se for ela.
     */
    private function excluirArena(Arena $arena): void
    {
        if (session('selected_arena_id') == $arena->id) {
            session()->forget('selected_arena_id');
        }

        $arena->delete(); // soft delete: histórico permanece
    }

    /**
     * Salva os horários de funcionamento de uma arena.
     *
     * Cada dia pode ter até 2 períodos (ex.: manhã e tarde, com pausa
     * para o almoço). Cada período preenchido vira uma linha em
     * arena_business_hours. Dias não marcados ficam sem registros (fechado).
     */
    public static function salvarHorarios(Arena $arena, array $horarios): void
    {
        for ($dia = 0; $dia <= 6; $dia++) {
            $info = $horarios[$dia] ?? [];

            if (empty($info['aberto'])) {
                continue;
            }

            $periodos = [
                ['abre' => $info['p1_abre'] ?? null, 'fecha' => $info['p1_fecha'] ?? null],
                ['abre' => $info['p2_abre'] ?? null, 'fecha' => $info['p2_fecha'] ?? null],
            ];

            foreach ($periodos as $periodo) {
                if (empty($periodo['abre']) || empty($periodo['fecha'])) {
                    continue;
                }

                $arena->businessHours()->create([
                    'day_of_week' => $dia,
                    'opens_at' => $periodo['abre'],
                    'closes_at' => $periodo['fecha'],
                ]);
            }
        }
    }

    /**
     * Valida os períodos de cada dia aberto (regra do MESMO DIA):
     * - o fechamento deve ser depois da abertura;
     * - o 2º período (se houver) deve fechar depois de abrir e começar
     *   depois do 1º terminar.
     * Retorna a mensagem do 1º problema encontrado, ou null se tudo certo.
     */
    public static function erroNosPeriodos(array $horarios): ?string
    {
        $nomes = [
            0 => 'domingo', 1 => 'segunda-feira', 2 => 'terça-feira',
            3 => 'quarta-feira', 4 => 'quinta-feira', 5 => 'sexta-feira', 6 => 'sábado',
        ];

        foreach ($horarios as $dia => $info) {
            if (empty($info['aberto'])) {
                continue;
            }

            $nome = $nomes[$dia] ?? "dia $dia";

            $p1a = $info['p1_abre'] ?? null;
            $p1f = $info['p1_fecha'] ?? null;

            if ($p1a && $p1f && $p1f <= $p1a) {
                return "Em {$nome}, a arena só pode abrir e fechar no mesmo dia (entre 00:01 e 23:59), com o fechamento depois da abertura.";
            }

            $p2a = $info['p2_abre'] ?? null;
            $p2f = $info['p2_fecha'] ?? null;

            if ($p2a && $p2f) {
                if ($p2f <= $p2a) {
                    return "Em {$nome}, o 2º período só pode abrir e fechar no mesmo dia (entre 00:01 e 23:59), com o fechamento depois da abertura.";
                }
                if ($p1f && $p2a < $p1f) {
                    return "Em {$nome}, o 2º período deve começar depois do 1º terminar.";
                }
            }
        }

        return null;
    }

    /**
     * Salva as quadras de uma arena (com seus esportes).
     *
     * Cada item de $quadras vira uma linha em courts, e cada esporte marcado
     * vira uma linha em court_sports. Uma arena precisa de ao menos uma quadra
     * (garantido pela validação no store).
     */
    public static function salvarQuadras(Arena $arena, array $quadras): void
    {
        foreach ($quadras as $dados) {
            $court = $arena->courts()->create([
                'name' => $dados['nome'],
                'hourly_rate' => $dados['valor_hora'],
                'active' => ! empty($dados['ativa']),
                'description' => $dados['descricao'] ?? null,
            ]);

            // array_unique evita duplicar o mesmo esporte (unique court_id+sport).
            foreach (array_unique($dados['esportes'] ?? []) as $sport) {
                $court->sports()->create(['sport' => $sport]);
            }
        }
    }

    /**
     * Normaliza um texto: remove espaços das pontas e colapsa espaços internos
     * repetidos em um só. Assim "Arena  X " vira "Arena X", impedindo burlar a
     * unicidade só mudando os espaços. Use em campos únicos (nome, empresa...).
     */
    public static function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return preg_replace('/\s+/u', ' ', trim($valor));
    }

    /**
     * Normaliza um e-mail: tira espaços e deixa minúsculo (e-mail é
     * case-insensitive na prática). Retorna null se ficar vazio, para que
     * campos opcionais sejam gravados como null em vez de string vazia.
     */
    public static function normalizarEmail(?string $valor): ?string
    {
        $valor = mb_strtolower(trim((string) $valor));

        return $valor === '' ? null : $valor;
    }

    /**
     * Diz se o e-mail já está em uso por uma arena de OUTRO proprietário.
     * O mesmo dono pode reusar o e-mail em várias arenas dele; só não pode
     * colidir com a arena de outro dono. Passe $ownerId null quando o dono
     * ainda não existe (cadastro novo) — aí qualquer arena conta como "de outro".
     */
    public static function emailDeArenaEmUsoPorOutroDono(?string $email, ?int $ownerId): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        return Arena::where('contact_email', $email)
            ->when($ownerId, fn ($q) => $q->where('owner_id', '!=', $ownerId))
            ->exists();
    }

    /**
     * Diz se o e-mail pertence à conta de OUTRO usuário (users.email).
     * Assim o contato da arena não pode ser o e-mail de outra pessoa
     * (ex.: um cliente). Passe o id do usuário atual para liberar o próprio.
     */
    public static function emailPertenceAOutroUsuario(?string $email, ?int $userId): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        return User::where('email', $email)
            ->when($userId, fn ($q) => $q->where('id', '!=', $userId))
            ->exists();
    }

    /**
     * Gera a "chave" de comparação de unicidade: minúsculo e SEM nenhum espaço.
     * Assim "Arena  X", "arena x" e "arenax" viram todos "arenax", impedindo
     * burlar a unicidade adicionando/removendo espaços ou trocando a caixa.
     */
    public static function chaveComparacao(?string $valor): string
    {
        return preg_replace('/\s+/u', '', mb_strtolower(trim((string) $valor)));
    }

    /**
     * Verifica se há nomes de quadra equivalentes (mesma chaveComparacao)
     * dentro do mesmo envio.
     */
    public static function temNomesDeQuadraDuplicados(array $quadras): bool
    {
        $chaves = [];

        foreach ($quadras as $dados) {
            $nome = $dados['nome'] ?? '';
            if ($nome === '') {
                continue;
            }

            $chave = self::chaveComparacao($nome);
            if (in_array($chave, $chaves, true)) {
                return true;
            }
            $chaves[] = $chave;
        }

        return false;
    }

    /**
     * Normaliza os nomes das quadras (aplica normalizarTexto em cada nome).
     */
    public static function normalizarNomesQuadras(array $quadras): array
    {
        foreach ($quadras as $i => $dados) {
            if (isset($dados['nome'])) {
                $quadras[$i]['nome'] = self::normalizarTexto($dados['nome']);
            }
        }

        return $quadras;
    }
}
