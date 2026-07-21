<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ArenaController;
use App\Http\Controllers\Concerns\RenovaSessaoAposTrocaDeSenha;
use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Court;
use App\Models\Employee;
use App\Models\Feedback;
use App\Models\Owner;
use App\Models\SystemAdmin;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    use RenovaSessaoAposTrocaDeSenha;

    public function index()
    {
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();
        $faturamentoPorArena = DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('courts', 'courts.id', '=', 'bookings.court_id')
            ->where('payments.status', 'paid')
            ->whereBetween('payments.paid_at', [$inicioMes, $fimMes])
            ->groupBy('courts.arena_id')
            ->selectRaw('courts.arena_id, COALESCE(SUM(payments.amount), 0) as total')
            ->pluck('total', 'courts.arena_id');

        $resumo = [
            'proprietarios' => Owner::count(),
            'proprietarios_ativos' => Owner::where('active', true)->count(),
            'proprietarios_inativos' => Owner::where('active', false)->count(),
            'arenas' => Arena::count(),
            'arenas_ativas' => Arena::where('active', true)->count(),
            'arenas_inativas' => Arena::where('active', false)->count(),
            'quadras' => Court::count(),
            'quadras_ativas' => Court::where('active', true)->count(),
            'quadras_inativas' => Court::where('active', false)->count(),
            // Conta só clientes cujo usuário ainda existe. Ao excluir um cliente o
            // usuário fica soft-deleted, mas a linha em `clients` permanece (as reservas
            // apontam para ela — é assim que o histórico é preservado). Sem o
            // whereHas('user'), o total não fecharia com ativos + inativos.
            'clientes' => Client::whereHas('user')->count(),
            'clientes_ativos' => Client::whereHas('user', fn ($q) => $q->where('active', true))->count(),
            'clientes_inativos' => Client::whereHas('user', fn ($q) => $q->where('active', false))->count(),
            'funcionarios' => Employee::count(),
            'funcionarios_ativos' => Employee::where('active', true)->count(),
            'funcionarios_inativos' => Employee::where('active', false)->count(),
            'administradores' => User::where('type', 'admin')->count(),
            'usuarios' => User::where('type', '!=', 'admin')->count(),
            'usuarios_ativos' => User::where('type', '!=', 'admin')->where('active', true)->count(),
            'usuarios_inativos' => User::where('type', '!=', 'admin')->where('active', false)->count(),
            'reservas_mes' => Booking::whereBetween('date', [
                $inicioMes->toDateString(),
                $fimMes->toDateString(),
            ])->count(),
            'faturamento_bruto' => (float) $faturamentoPorArena->sum(),
        ];

        // Sugestões/bugs ainda não vistos pelo admin (badge no botão).
        $feedbacksNaoLidos = Feedback::whereNull('read_at')->count();

        return view('admin.dashboard', compact(
            'resumo',
            'feedbacksNaoLidos'
        ));
    }

    /**
     * Lista todos os usuários do sistema (exceto administradores), com seus
     * dados — sem a senha. Alimenta a tela aberta pelo card "Total de usuários".
     */
    public function usuarios()
    {
        $q = trim((string) request('q'));

        // Filtro por tipo: cliente, funcionário ou proprietário (admin já é excluído).
        $tipo = request('tipo');
        $tiposValidos = ['client', 'employee', 'owner'];

        $usuarios = User::where('type', '!=', 'admin')
            ->when(in_array($tipo, $tiposValidos, true), fn ($query) => $query->where('type', $tipo))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.system.usuarios', compact('usuarios', 'tipo'));
    }

    /**
     * Tela dedicada com os detalhes de um usuário (cliente, funcionário ou
     * administrador) — sem a senha. Para cliente, mostra também as reservas
     * (dados que crescem com o tempo, por isso tela e não modal).
     */
    public function showUser(User $user)
    {
        $user->load([
            'client',
            'employee.arena.owner.user',
            'employee.createdBy',
        ]);

        // Ordem escolhida no topo da lista; padrão número decrescente, para a
        // coluna Nº não embaralhar (ver Booking::scopeOrdenado).
        $ordenar = Booking::ordemValida(request('ordenar'));

        $reservas = collect();
        $numerosCliente = [];
        if ($user->client) {
            $reservas = Booking::with(['court.arena', 'payments.paymentMethod'])
                ->where('client_id', $user->client->id)
                ->ordenado($ordenar)
                ->paginate(20)->withQueryString();

            // Números da sequência do cliente em lote: sem isto, a view chamaria
            // numeroDoCliente() por linha, um COUNT cada.
            $numerosCliente = Booking::numerosDoCliente($user->client->id);
        }

        return view('admin.users.show', compact('user', 'reservas', 'ordenar', 'numerosCliente'));
    }

    /**
     * Query paginada de clientes com reservas nas quadras informadas
     * (com nº de reservas e total gasto). Reusada pelas telas de clientes.
     */
    private function clientesComReservas($idsQuadras)
    {
        $busca = trim((string) request('busca_cliente'));
        $chave = preg_replace('/\s+/u', '', mb_strtolower($busca));

        return Client::with('user')
            ->whereIn('id', Booking::select('client_id')->whereIn('court_id', clone $idsQuadras)->distinct())
            ->when($chave !== '', function ($query) use ($chave) {
                $termo = '%' . $chave . '%';
                $query->whereHas('user', function ($user) use ($termo) {
                    $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(email), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(COALESCE(phone, '')), ' ', '') LIKE ?", [$termo]);
                });
            })
            ->select('clients.*')
            ->selectSub(
                Booking::selectRaw('COUNT(*)')->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras),
                'reservas_total'
            )
            ->selectSub(
                // Total gasto = o que o cliente REALMENTE pagou. Sai da tabela
                // `payments`, não do valor das reservas: a fórmula antiga somava
                // o total_amount de toda reserva não cancelada, incluindo as que
                // nunca foram pagas — inflava o "gasto" com dívida.
                //
                // Pagamentos 'paid' já trazem o líquido (desconto abatido) e as
                // taxas de cancelamento pagas; os reembolsos abatem o que voltou.
                Booking::selectRaw("
                    COALESCE(SUM(
                        COALESCE((SELECT SUM(pg.amount) FROM payments pg
                                   WHERE pg.booking_id = bookings.id AND pg.status = 'paid'), 0)
                      - COALESCE((SELECT SUM(pr.refund_amount) FROM payments pr
                                   WHERE pr.booking_id = bookings.id AND pr.refunded_at IS NOT NULL), 0)
                    ), 0)
                ")
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras),
                'valor_total'
            )
            ->orderByDesc('reservas_total')
            ->orderBy(User::select('name')->whereColumn('users.id', 'clients.user_id'))
            ->paginate(25)
            ->appends(['busca_cliente' => $busca]);
    }

    /**
     * Tela dedicada: clientes de uma empresa (antes era modal).
     */
    public function ownerClientsPage(Owner $owner)
    {
        $idsQuadras = Court::withTrashed()
            ->whereIn('arena_id', $owner->arenas()->select('arenas.id'))
            ->select('id');

        $clientes = $this->clientesComReservas($idsQuadras);

        return view('admin.owners.clients-page', compact('owner', 'clientes'));
    }

    /**
     * Tela dedicada: clientes de uma arena (antes era modal).
     */
    public function arenaClientsPage(Arena $arena)
    {
        $idsQuadras = Court::withTrashed()->where('arena_id', $arena->id)->select('id');

        $clientes = $this->clientesComReservas($idsQuadras);

        return view('admin.arenas.clients-page', compact('arena', 'clientes'));
    }

    /**
     * Tela dedicada: reservas de uma arena (mês / canceladas / histórico).
     */
    public function arenaReservasPage(Arena $arena)
    {
        $idsQuadras = Court::withTrashed()->where('arena_id', $arena->id)->select('id');

        // Ordem escolhida no topo. Padrão: número crescente, para a coluna Nº
        // não embaralhar (ver Booking::scopeOrdenado).
        $ordenar = Booking::ordemValida(request('ordenar_reserva'));

        // Mês selecionado no topo (?mes=AAAA-MM), padrão o mês atual. Filtra a
        // PÁGINA TODA: as três abas passam a ser do mesmo mês (reservas,
        // atrasados e canceladas daquele mês). Sem isto a aba de mês ficava
        // presa em now() e os outros meses não tinham como ser alcançados.
        $mesRef = now()->startOfMonth();
        if (preg_match('/^(\d{4})-(\d{2})$/', (string) request('mes'), $pm)
            && (int) $pm[2] >= 1 && (int) $pm[2] <= 12) {
            $mesRef = \Carbon\Carbon::create((int) $pm[1], (int) $pm[2], 1)->startOfMonth();
        }

        // Total de todas as reservas da arena, de todos os meses — mostrado ao
        // lado do título, para separar o "acervo inteiro" do que as abas
        // (filtradas pelo mês) exibem.
        $reservasTotalArena = Booking::whereIn('court_id', clone $idsQuadras)->count();

        $busca = trim((string) request('busca_reserva'));
        $chave = preg_replace('/\s+/u', '', mb_strtolower($busca));
        $data = null;
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $busca, $p) && checkdate((int) $p[2], (int) $p[1], (int) $p[3])) {
            $data = sprintf('%04d-%02d-%02d', $p[3], $p[2], $p[1]);
        }
        $temBusca = $busca !== '';

        $consulta = Booking::with(['courtWithTrashed', 'client.user', 'cancelledBy', 'payments.paymentMethod'])
            ->whereIn('court_id', clone $idsQuadras)
            ->ordenado($ordenar);

        // O mês filtra a página SÓ quando não há busca. Buscando um nome ou uma
        // data, procura em TODOS os meses — senão um cliente com reserva em
        // outro mês nunca apareceria. A busca vale mais que o mês.
        if (! $temBusca) {
            $consulta->whereBetween('date', [$mesRef->toDateString(), $mesRef->copy()->endOfMonth()->toDateString()]);
        }

        $consulta->when($chave !== '' && ! $data, function ($query) use ($chave) {
            $termo = '%' . $chave . '%';
            $query->whereHas('client.user', fn ($u) => $u->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo]));
        })->when($data, fn ($query) => $query->whereDate('date', $data));

        $aba = request('aba_reservas', 'mes');

        // ordenar_reserva e mes viajam com a paginação para não se perderem ao
        // trocar de página em qualquer aba.
        $filtros = [
            'busca_reserva' => $busca, 'aba_reservas' => $aba,
            'ordenar_reserva' => $ordenar, 'mes' => $mesRef->format('Y-m'),
        ];

        // Todas as reservas do mês (o mês já está no $consulta base).
        $reservasMesLista = (clone $consulta)
            ->paginate(25, ['*'], 'mes_page')->appends($filtros);
        $reservasCanceladasLista = (clone $consulta)->where('status', 'cancelled')
            ->paginate(25, ['*'], 'canceladas_page')->appends($filtros);

        // Pagamentos atrasados do mês: confirmada/realizada, sem pagamento pago e
        // cujo horário já terminou (mesma regra do Booking::situacaoPagamento()).
        $reservasAtrasadasLista = (clone $consulta)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->where(function ($q) {
                $q->whereDate('date', '<', now()->toDateString())
                    ->orWhere(fn ($q2) => $q2->whereDate('date', now()->toDateString())
                        ->where('end_time', '<', now()->format('H:i:s')));
            })
            ->paginate(25, ['*'], 'atrasados_page')->appends($filtros);

        $historicoReservasLista = (clone $consulta)
            ->paginate(25, ['*'], 'historico_page')->appends($filtros);

        return view('admin.arenas.reservas-page', compact(
            'arena', 'aba', 'busca', 'ordenar', 'mesRef', 'reservasTotalArena', 'temBusca',
            'reservasMesLista', 'reservasCanceladasLista', 'reservasAtrasadasLista', 'historicoReservasLista'
        ));
    }

    public function storeAdmin(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')),
        ]);

        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        DB::transaction(function () use ($dados) {
            $usuario = User::create([
                'name' => $dados['name'],
                'email' => mb_strtolower($dados['email']),
                'phone' => $dados['phone'],
                'password_hash' => $dados['password'],
                'terms_accepted_at' => now(),
                'active' => true,
                'type' => 'admin',
            ]);

            SystemAdmin::create([
                'user_id' => $usuario->id,
            ]);
        });

        return response()->json([
            'message' => 'Novo administrador cadastrado com sucesso.',
        ], 201);
    }

    public function systemAdmins()
    {
        $administradores = User::with('systemAdmin')
            ->where('type', 'admin')
            ->orderBy('name')
            ->get();

        return view('admin.system.administrators', compact('administradores'));
    }

    /**
     * O admin edita os próprios dados (nome, e-mail e telefone).
     *
     * A validação é manual porque a tela é um modal do painel: um erro de
     * validação padrão redirecionaria de volta sem nada visível (o layout mostra
     * `msg`/`aviso`, não a bag de erros). Assim a mensagem aparece no topo.
     */
    public function updateAdminProfile(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')),
        ]);

        $admin = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'Informe o nome.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso por outra conta.',
            'phone.max' => 'O telefone deve ter no máximo 30 caracteres.',
        ]);

        if ($validator->fails()) {
            return back()->with('aviso', $validator->errors()->first());
        }

        $dados = $validator->validated();

        $admin->update([
            'name' => $dados['name'],
            'email' => $dados['email'],
            'phone' => $dados['phone'] ?? null,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('msg', 'Perfil atualizado com sucesso.');
    }

    public function updateAdminPassword(Request $request)
    {
        $dados = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $admin = $request->user();
        if (! Hash::check($dados['current_password'], $admin->password_hash)) {
            return back()->with('msg', 'A senha atual está incorreta.');
        }

        $admin->update(['password_hash' => $dados['password']]);
        $this->renovarHashDaSenhaNaSessao($request);

        return redirect()->route('admin.dashboard')
            ->with('msg', 'Senha alterada com sucesso.');
    }

    public function destroyAdminAccount(Request $request)
    {
        $dados = $request->validate([
            'delete_password' => ['required', 'string'],
        ]);

        $admin = $request->user();
        if (! Hash::check($dados['delete_password'], $admin->password_hash)) {
            return back()->with('erro', 'A senha informada está incorreta. A conta não foi excluída.');
        }

        // O sistema NÃO pode ficar sem administrador: se este for o único que
        // sobrou (conta viva e ativa), a exclusão é bloqueada. Sem esta guarda,
        // ninguém mais conseguiria administrar o sistema.
        $outrosAdmins = SystemAdmin::where('user_id', '!=', $admin->id)
            ->whereHas('user', fn ($q) => $q->where('active', true))
            ->count();

        if ($outrosAdmins === 0) {
            return back()->with('erro', 'Você é o único administrador do sistema. Cadastre outro administrador antes de excluir a sua conta.');
        }

        DB::transaction(function () use ($admin) {
            $admin->update(['active' => false]);
            $admin->systemAdmin?->delete();

            // Encerra a conta (anonimiza, libera o e-mail, derruba a sessão).
            // O nome vira "Administrador removido #id": a FUNÇÃO fica para a
            // auditoria, o dado pessoal some.
            $admin->encerrarConta();
        });

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('msg', 'Conta de administrador excluída com sucesso.');
    }

    public function quickSearch(Request $request)
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:empresa,arena,quadra,usuario'],
            'busca' => ['nullable', 'string', 'max:120'],
        ]);

        $busca = trim((string) ($dados['busca'] ?? ''));
        if ($busca === '') {
            return response()->json(['resultados' => []]);
        }

        $chave = preg_replace('/\s+/u', '', mb_strtolower($busca));
        $termo = '%' . $chave . '%';

        if ($dados['tipo'] === 'empresa') {
            $resultados = Owner::with('user')
                ->where(function ($query) use ($termo) {
                    $query->whereRaw("REPLACE(LOWER(company_name), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(tax_id), ' ', '') LIKE ?", [$termo])
                        ->orWhereHas('user', function ($user) use ($termo) {
                            $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                                ->orWhereRaw("REPLACE(LOWER(email), ' ', '') LIKE ?", [$termo]);
                        });
                })
                ->orderBy('company_name')
                ->limit(15)
                ->get()
                ->map(fn ($empresa) => [
                    'id' => $empresa->id,
                    'nome' => $empresa->company_name,
                    'proprietario' => $empresa->user?->name ?? '—',
                    'documento' => $empresa->tax_id,
                    'ativo' => (bool) $empresa->active,
                    'ver_url' => route('admin.owners.show', $empresa),
                    'ativar_url' => route('admin.owners.activate', $empresa),
                    'desativar_url' => route('admin.owners.deactivate', $empresa),
                    'excluir_url' => route('admin.owners.destroy', $empresa),
                ]);
        } elseif ($dados['tipo'] === 'arena') {
            $resultados = Arena::with('owner.user')
                ->where(function ($query) use ($termo) {
                    $query->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereHas('owner', function ($owner) use ($termo) {
                            $owner->whereRaw("REPLACE(LOWER(company_name), ' ', '') LIKE ?", [$termo])
                                ->orWhereHas('user', fn ($user) => $user
                                    ->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo]));
                        });
                })
                ->orderBy('name')
                ->limit(15)
                ->get()
                ->map(fn ($arena) => [
                    'id' => $arena->id,
                    'nome' => $arena->name,
                    'empresa' => $arena->owner?->company_name ?? '—',
                    'ativo' => (bool) $arena->active,
                    'ver_url' => route('admin.arenas.show', [
                        'arena' => $arena,
                        'origem' => 'arenas_sistema',
                    ]),
                    'ativar_url' => route('admin.arenas.activate', $arena),
                    'desativar_url' => route('admin.arenas.deactivate', $arena),
                    'excluir_url' => route('admin.arenas.destroy', $arena),
                ]);
        } elseif ($dados['tipo'] === 'quadra') {
            $resultados = Court::with('arena.owner')
                ->where(function ($query) use ($termo) {
                    $query->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereHas('arena', function ($arena) use ($termo) {
                            $arena->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                                ->orWhereHas('owner', fn ($owner) => $owner
                                    ->whereRaw("REPLACE(LOWER(company_name), ' ', '') LIKE ?", [$termo]));
                        });
                })
                ->orderBy('name')
                ->limit(15)
                ->get()
                ->map(fn ($quadra) => [
                    'id' => $quadra->id,
                    'nome' => $quadra->name,
                    'arena' => $quadra->arena?->name ?? '—',
                    'empresa' => $quadra->arena?->owner?->company_name ?? '—',
                    'ativo' => (bool) $quadra->active,
                    'ver_url' => $quadra->arena
                        ? route('admin.arenas.show', [
                            'arena' => $quadra->arena,
                            'origem' => 'quadras_sistema',
                            'quadras_modal' => 1,
                        ])
                        : route('admin.system.courts'),
                    'ativar_url' => $quadra->arena
                        ? route('admin.arenas.courts.activate', [$quadra->arena, $quadra])
                        : null,
                    'desativar_url' => $quadra->arena
                        ? route('admin.arenas.courts.deactivate', [$quadra->arena, $quadra])
                        : null,
                    'excluir_url' => $quadra->arena
                        ? route('admin.arenas.courts.destroy', [$quadra->arena, $quadra])
                        : null,
                ]);
        } else {
            $resultados = User::with(['owner', 'employee.arena.owner'])
                ->where(function ($query) use ($termo) {
                    $query->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(email), ' ', '') LIKE ?", [$termo]);
                })
                ->orderBy('name')
                ->limit(15)
                ->get()
                ->map(function ($usuario) {
                    $tipo = match ($usuario->type) {
                        'admin' => 'Administrador',
                        'owner' => 'Proprietário',
                        'employee' => 'Funcionário',
                        default => 'Cliente',
                    };

                    $verUrl = match ($usuario->type) {
                        'owner' => $usuario->owner
                            ? route('admin.owners.show', $usuario->owner)
                            : route('admin.owners.index'),
                        'employee' => $usuario->employee?->arena
                            ? route('admin.arenas.show', $usuario->employee->arena)
                            : route('admin.system.employees'),
                        'client' => route('admin.system.clients', ['busca_cliente' => $usuario->email]),
                        default => route('admin.dashboard'),
                    };

                    return [
                        'id' => $usuario->id,
                        'nome' => $usuario->name,
                        'email' => $usuario->email,
                        'tipo' => $tipo,
                        'arena' => $usuario->employee?->arena?->name,
                        'empresa' => $usuario->employee?->arena?->owner?->company_name,
                        'ativo' => (bool) $usuario->active,
                        'pode_alterar' => ! $usuario->is(auth()->user()),
                        'ver_url' => $verUrl,
                        'bloquear_url' => route('admin.users.block', $usuario),
                        'desbloquear_url' => route('admin.users.unblock', $usuario),
                        'excluir_url' => route('admin.users.destroy', $usuario),
                    ];
                });
        }

        return response()->json(['resultados' => $resultados->values()]);
    }

    public function systemArenas()
    {
        $arenas = Arena::with([
            'owner.user',
            'paymentMethods',
            'businessHours' => fn ($query) => $query->orderBy('day_of_week')->orderBy('opens_at'),
        ])
            ->withCount(['courts', 'employees'])
            ->orderBy(
                Owner::select('company_name')
                    ->whereColumn('owners.id', 'arenas.owner_id')
            )
            ->orderBy('name')
            ->get();

        return view('admin.system.arenas', compact('arenas'));
    }

    public function systemCourts()
    {
        $quadras = Court::with(['arena.owner.user', 'sports'])
            ->get()
            ->sortBy(fn ($quadra) => mb_strtolower(implode('|', [
                $quadra->arena?->owner?->company_name ?? '',
                $quadra->arena?->name ?? '',
                $quadra->name,
            ])))
            ->values();

        return view('admin.system.courts', compact('quadras'));
    }

    public function systemEmployees()
    {
        $funcionarios = Employee::with([
            'user',
            'arena.owner.user',
            'createdBy',
        ])
            ->get()
            ->sortBy(fn ($funcionario) => mb_strtolower(implode('|', [
                $funcionario->arena?->owner?->company_name ?? '',
                $funcionario->arena?->name ?? '',
                $funcionario->user?->name ?? '',
            ])))
            ->values();

        return view('admin.system.employees', compact('funcionarios'));
    }

    public function systemClients()
    {
        $usuarios = $this->systemClientsQuery()
            ->simplePaginate(25)
            ->appends(['busca_cliente' => request('busca_cliente')]);

        $dividas = $this->dividasDosClientes($usuarios);

        return view('admin.system.users', compact('usuarios', 'dividas'));
    }

    public function systemClientsData()
    {
        $usuarios = $this->systemClientsQuery()
            ->simplePaginate(25)
            ->appends(['busca_cliente' => request('busca_cliente')]);

        $dividas = $this->dividasDosClientes($usuarios);

        return response()->json([
            'html' => view('admin.system._client-rows', compact('usuarios', 'dividas'))->render(),
            'next_url' => $usuarios->nextPageUrl(),
        ]);
    }

    /**
     * Quanto cada cliente da página deve, indexado por id de cliente.
     *
     * O admin pode excluir mesmo assim — é ação administrativa —, mas precisa
     * ver o valor antes de confirmar, para não apagar uma dívida sem saber.
     *
     * Resolve a página inteira numa consulta só: perguntar por linha custaria
     * 25 consultas a cada carga, e esta lista tem rolagem infinita.
     */
    private function dividasDosClientes($usuarios): \Illuminate\Support\Collection
    {
        $clienteIds = collect($usuarios->items())
            ->pluck('client.id')
            ->filter()
            ->all();

        if (! $clienteIds) {
            return collect();
        }

        return Booking::whereIn('client_id', $clienteIds)
            ->emAberto()
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) AS quantidade, SUM(total_amount) AS total')
            ->get()
            ->keyBy('client_id');
    }

    private function systemClientsQuery()
    {
        $busca = trim((string) request('busca_cliente'));
        $chave = preg_replace('/\s+/u', '', mb_strtolower($busca));

        return User::with('client')
            ->where('type', 'client')
            ->when($chave !== '', function ($query) use ($chave) {
                $termo = '%' . $chave . '%';

                $query->where(function ($filtro) use ($termo) {
                    $filtro->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(email), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(COALESCE(phone, '')), ' ', '') LIKE ?", [$termo]);
                });
            })
            ->orderBy('name');
    }

    public function blockUser(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('msg', 'Você não pode bloquear o próprio usuário administrador.');
        }

        // Bloquear só o usuário deixaria a empresa e as arenas ativas (estado
        // incoerente: owner.active = 1 com user.active = 0). A desativação de uma
        // empresa tem regra própria — inclui arenas, quadras e cancelamento de reservas.
        if ($user->owner) {
            return back()->with('aviso', 'Este usuário é proprietário. Use a tela de Proprietários para desativar a empresa — isso também trata as arenas, as quadras e as reservas.');
        }

        DB::transaction(function () use ($user) {
            $user->update(['active' => false]);

            // Cliente bloqueado: cancela as reservas futuras (libera os horários).
            if ($user->client) {
                $this->cancelarReservasFuturasDoCliente(
                    $user->client->id,
                    'Cliente bloqueado pelo administrador geral.'
                );
            }

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('personal_access_tokens')) {
                $user->tokens()->delete();
            }
        });

        return back()->with('msg', 'Usuário bloqueado e sessões encerradas com sucesso.');
    }

    public function unblockUser(User $user)
    {
        $user->update(['active' => true]);

        return back()->with('msg', 'Usuário desbloqueado com sucesso.');
    }

    public function destroyUser(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('msg', 'Você não pode excluir o próprio usuário administrador.');
        }

        // Excluir só o usuário deixaria a empresa e as arenas órfãs e ativas.
        // A exclusão de empresa tem regra própria (arenas, quadras e reservas).
        if ($user->owner) {
            return back()->with('aviso', 'Este usuário é proprietário. Use a tela de Proprietários para excluir a empresa — isso também trata as arenas, as quadras e as reservas.');
        }

        $client = $user->client;

        DB::transaction(function () use ($user, $client) {
            $user->update(['active' => false]);

            if ($client) {
                // Ação ADMINISTRATIVA: ao contrário da autoexclusão do cliente,
                // não há trava por reserva ativa ou pagamento pendente — o admin
                // executa mesmo assim. As reservas futuras são canceladas (com
                // aviso e reembolso do que estava pago) e os dados pessoais das
                // reservas são apagados, igual à autoexclusão.
                $this->cancelarReservasFuturasDoCliente(
                    $client->id,
                    'Cliente excluído pelo administrador geral.'
                );

                $client->desligarReservasAnonimizando();
                $client->anonimizarDadosPessoais();
                $client->delete();
            }

            $employee = $user->employee;
            $employee?->update(['active' => false]);
            $user->systemAdmin?->delete();

            // Cliente: o nome também é apagado — ele não é da equipe, então não
            // há trilha de auditoria a preservar, e a reserva já não o identifica.
            // Funcionário/admin: o nome fica, é quem responde pelo que foi feito.
            $user->encerrarConta();

            $employee?->delete();
        });

        return back()->with('msg', 'Usuário excluído. O histórico foi preservado e o e-mail liberado para um novo cadastro.');
    }

    /**
     * Cancela as reservas ativas (pendentes/confirmadas) das quadras informadas.
     * Usado quando o admin desativa/exclui uma empresa (todas as arenas dela),
     * mantendo a agenda coerente — mesma regra de desativar uma arena.
     */
    private function cancelarReservasAtivasDasQuadras($courtIds, string $motivo): void
    {
        // cancelarEmLote: cancela E avisa cada cliente com o motivo. Antes era um
        // update direto, que pulava o model e deixava o cliente sem saber.
        Booking::cancelarEmLote(
            Booking::whereIn('court_id', $courtIds)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get(),
            $motivo,
            auth()->id()
        );
    }

    /**
     * Cancela as reservas FUTURAS (a partir de hoje) de um cliente, liberando os
     * horários na agenda. Usado ao bloquear/excluir o cliente. O histórico passado
     * é preservado.
     */
    private function cancelarReservasFuturasDoCliente(int $clientId, string $motivo): void
    {
        // cancelarEmLote também REEMBOLSA o que estava pago: o cliente perde as
        // reservas por decisão do admin, então não pode perder o dinheiro junto.
        // O aviso ainda chega, porque isto roda ANTES de a conta ser encerrada.
        Booking::cancelarEmLote(
            Booking::where('client_id', $clientId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('date', '>=', now()->toDateString())
                ->get(),
            $motivo,
            auth()->id()
        );
    }

    public function owners()
    {
        $proprietarios = Owner::with(['user', 'deactivatedBy'])
            ->withCount([
                'arenas',
                'arenas as arenas_ativas_count' => fn ($query) => $query->where('active', true),
            ])
            ->selectSub(
                Court::selectRaw('COUNT(*)')
                    ->join('arenas', 'arenas.id', '=', 'courts.arena_id')
                    ->whereColumn('arenas.owner_id', 'owners.id'),
                'quadras_count'
            )
            ->selectSub(
                Employee::selectRaw('COUNT(*)')
                    ->join('arenas', 'arenas.id', '=', 'employees.arena_id')
                    ->whereColumn('arenas.owner_id', 'owners.id'),
                'funcionarios_count'
            )
            ->orderBy('company_name')
            ->get();

        return view('admin.owners.index', compact('proprietarios'));
    }

    public function ownerDetails(Owner $owner)
    {
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        // Faturamento REAL (via caixa): líquido do mês por arena, para os cards.
        $arenaIdsEmpresa = $owner->arenas()->pluck('arenas.id')->all();
        $faturamentoPorArena = collect($arenaIdsEmpresa)->mapWithKeys(fn ($aid) => [
            $aid => \App\Support\Faturamento::liquido(
                [$aid], $inicioMes->toDateString(), $fimMes->toDateString()
            ),
        ]);

        $owner->load('user');

        $arenas = $owner->arenas()
            ->with([
                'courts.sports',
                'courts.arena',
                'employees.user',
                'employees.arena',
                'employees.createdBy',
            ])
            ->withCount(['courts', 'employees'])
            ->orderBy('name')
            ->get()
            ->map(function ($arena) use ($faturamentoPorArena) {
                $arena->faturamento_mes = (float) ($faturamentoPorArena[$arena->id] ?? 0);

                return $arena;
            });

        $totais = [
            'arenas' => $arenas->count(),
            'quadras' => $arenas->sum('courts_count'),
            'funcionarios' => $arenas->sum('employees_count'),
            'faturamento_mes' => $arenas->sum('faturamento_mes'),
        ];

        // Faturamento REAL da empresa (todas as arenas): breakdown por ano + mensal.
        $anoFaturamentoEmpresa = (int) request('ano_faturamento', now()->year);
        $anosFaturamentoEmpresa = \App\Support\Faturamento::anos($arenaIdsEmpresa);
        if (! $anosFaturamentoEmpresa->contains($anoFaturamentoEmpresa)) {
            $anoFaturamentoEmpresa = (int) now()->year;
        }

        $fatMesEmpresa = \App\Support\Faturamento::resumo(
            $arenaIdsEmpresa, $inicioMes->toDateString(), $fimMes->toDateString()
        );
        $fatAcumuladoEmpresa = \App\Support\Faturamento::resumo($arenaIdsEmpresa);
        $fatAnoEmpresa = \App\Support\Faturamento::resumo(
            $arenaIdsEmpresa,
            $anoFaturamentoEmpresa . '-01-01',
            $anoFaturamentoEmpresa . '-12-31'
        );
        $fatMensalEmpresa = \App\Support\Faturamento::mensal($arenaIdsEmpresa, $anoFaturamentoEmpresa);

        $idsQuadrasEmpresa = Court::withTrashed()
            ->whereIn('arena_id', $owner->arenas()->select('arenas.id'))
            ->select('id');

        $buscaClienteEmpresa = trim((string) request('busca_cliente'));
        $chaveBuscaClienteEmpresa = preg_replace('/\s+/u', '', mb_strtolower($buscaClienteEmpresa));

        $clientesEmpresa = Client::with('user')
            ->whereIn(
                'id',
                Booking::select('client_id')
                    ->whereIn('court_id', clone $idsQuadrasEmpresa)
                    ->distinct()
            )
            ->when($chaveBuscaClienteEmpresa !== '', function ($query) use ($chaveBuscaClienteEmpresa) {
                $termo = '%' . $chaveBuscaClienteEmpresa . '%';

                $query->whereHas('user', function ($user) use ($termo) {
                    $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(email), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(COALESCE(phone, '')), ' ', '') LIKE ?", [$termo]);
                });
            })
            ->select('clients.*')
            ->selectSub(
                Booking::selectRaw('COUNT(*)')
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadrasEmpresa),
                'reservas_na_empresa'
            )
            ->selectSub(
                // Total gasto = o que o cliente REALMENTE pagou (payments 'paid'
                // já líquidos, menos reembolsos). A fórmula antiga somava o valor
                // de toda reserva não cancelada, inclusive as não pagas, inflando
                // o gasto com dívida. Ver clientesComReservas.
                Booking::selectRaw("COALESCE(SUM(COALESCE((SELECT SUM(pg.amount) FROM payments pg WHERE pg.booking_id = bookings.id AND pg.status = 'paid'), 0) - COALESCE((SELECT SUM(pr.refund_amount) FROM payments pr WHERE pr.booking_id = bookings.id AND pr.refunded_at IS NOT NULL), 0)), 0)")
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadrasEmpresa),
                'valor_total_na_empresa'
            )
            ->orderByDesc('reservas_na_empresa')
            ->orderBy(
                User::select('name')->whereColumn('users.id', 'clients.user_id')
            )
            ->paginate(25, ['*'], 'clientes_page')
            ->appends([
                'clientes_modal' => 1,
                'busca_cliente' => $buscaClienteEmpresa,
            ]);

        $empresas = Owner::orderBy('company_name')->get(['id', 'company_name']);

        return view('admin.owners.show', compact(
            'owner',
            'arenas',
            'totais',
            'empresas',
            'clientesEmpresa',
            'anoFaturamentoEmpresa',
            'anosFaturamentoEmpresa',
            'fatMesEmpresa',
            'fatAcumuladoEmpresa',
            'fatAnoEmpresa',
            'fatMensalEmpresa'
        ));
    }

    public function ownerClients(Owner $owner)
    {
        $idsQuadras = Court::withTrashed()
            ->whereIn('arena_id', $owner->arenas()->select('arenas.id'))
            ->select('id');
        $busca = trim((string) request('busca_cliente'));
        $chave = preg_replace('/\s+/u', '', mb_strtolower($busca));

        $clientes = Client::with('user')
            ->whereIn('id', Booking::select('client_id')
                ->whereIn('court_id', clone $idsQuadras)->distinct())
            ->when($chave !== '', function ($query) use ($chave) {
                $termo = '%' . $chave . '%';
                $query->whereHas('user', function ($user) use ($termo) {
                    $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(email), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(COALESCE(phone, '')), ' ', '') LIKE ?", [$termo]);
                });
            })
            ->select('clients.*')
            ->selectSub(
                Booking::selectRaw('COUNT(*)')
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras),
                'reservas_total'
            )
            ->selectSub(
                // Total gasto = o que o cliente REALMENTE pagou (payments 'paid'
                // já líquidos, menos reembolsos). A fórmula antiga somava o valor
                // de toda reserva não cancelada, inclusive as não pagas, inflando
                // o gasto com dívida. Ver clientesComReservas.
                Booking::selectRaw("COALESCE(SUM(COALESCE((SELECT SUM(pg.amount) FROM payments pg WHERE pg.booking_id = bookings.id AND pg.status = 'paid'), 0) - COALESCE((SELECT SUM(pr.refund_amount) FROM payments pr WHERE pr.booking_id = bookings.id AND pr.refunded_at IS NOT NULL), 0)), 0)")
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras),
                'valor_total'
            )
            ->orderByDesc('reservas_total')
            ->orderBy(User::select('name')->whereColumn('users.id', 'clients.user_id'))
            ->simplePaginate(25)
            ->appends(['busca_cliente' => $busca]);

        return response()->json([
            'html' => view('admin.clients._rows', compact('clientes'))->render(),
            'next_url' => $clientes->nextPageUrl(),
        ]);
    }

    public function deactivateOwner(Owner $owner)
    {
        DB::transaction(function () use ($owner) {
            $owner->load('user');
            $owner->update([
                'active' => false,
                'deactivated_by' => auth()->id(),
                'deactivation_source' => 'admin',
                'deactivated_at' => now(),
            ]);
            $owner->user?->update(['active' => false]);

            // Cancela as reservas ativas de TODAS as arenas da empresa (mesma regra
            // de desativar uma arena) — antes de desativar as quadras.
            $courtIds = Court::whereIn('arena_id', $owner->arenas()->select('arenas.id'))->pluck('id');
            $this->cancelarReservasAtivasDasQuadras($courtIds, 'Empresa desativada pelo administrador geral.');

            // Avisa o dono ANTES de trancar o acesso: a conta dele é desativada
            // aqui, então sem este e-mail ele apenas descobriria que não
            // consegue mais entrar, sem saber o motivo.
            foreach ($owner->arenas as $arena) {
                UserNotification::paraDonoDaArena(
                    $arena,
                    'Empresa desativada pelo administrador',
                    'A empresa "'.$owner->company_name.'" foi desativada pelo administrador do sistema. '
                        .'As arenas saíram do ar, as reservas ativas foram canceladas e o acesso está suspenso.',
                    auth()->id()
                );
                break; // um aviso por empresa, não por arena
            }

            // Fecha o caixa aberto de cada arena (o reembolso do cancelamento
            // acima já entrou como saída antes do fechamento).
            foreach ($owner->arenas as $arena) {
                ArenaController::fecharCaixaAbertoDaArena($arena);
            }

            $owner->arenas()->update(['active' => false]);

            Court::whereIn('arena_id', $owner->arenas()->select('arenas.id'))
                ->update(['active' => false]);

            if ($owner->user) {
                DB::table('sessions')->where('user_id', $owner->user->id)->delete();
                if (Schema::hasTable('personal_access_tokens')) {
                    $owner->user->tokens()->delete();
                }
            }
        });

        return redirect()->route('admin.owners.index')
            ->with('msg', 'Empresa desativada e reservas ativas canceladas.');
    }

    public function activateOwner(Owner $owner)
    {
        DB::transaction(function () use ($owner) {
            $owner->load('user');
            $owner->update([
                'active' => true,
                'deactivated_by' => null,
                'deactivation_source' => null,
                'deactivated_at' => null,
            ]);
            $owner->user?->update(['active' => true]);
            $owner->arenas()->update(['active' => true]);
            Court::whereIn('arena_id', $owner->arenas()->select('arenas.id'))
                ->update(['active' => true]);
        });

        return back()->with('msg', 'Empresa ativada com sucesso.');
    }

    public function destroyOwner(Owner $owner)
    {
        DB::transaction(function () use ($owner) {
            $owner->load('user');

            // Cancela as reservas ativas de TODAS as arenas da empresa antes de excluir.
            $courtIds = Court::whereIn('arena_id', $owner->arenas()->select('arenas.id'))->pluck('id');
            $this->cancelarReservasAtivasDasQuadras($courtIds, 'Empresa excluída pelo administrador geral.');

            $owner->arenas()->update(['active' => false]);
            Court::whereIn('arena_id', $owner->arenas()->select('arenas.id'))
                ->update(['active' => false]);

            foreach ($owner->arenas as $arena) {
                // Mesmas regras de excluir uma arena: apura e fecha o caixa
                // aberto e encerra os funcionários dela.
                ArenaController::fecharCaixaAbertoDaArena($arena);
                ArenaController::encerrarFuncionariosDaArena($arena);
                $arena->anonimizarContato(); // telefone/e-mail somem
                $arena->delete();
            }

            $owner->user?->update(['active' => false]);

            // Os DADOS PESSOAIS do dono somem; o nome vira "Proprietário
            // removido #id". O registro do negócio é da EMPRESA: company_name e
            // tax_id ficam guardados, e é a pessoa jurídica que responde pela
            // contabilidade.
            $owner->user?->encerrarConta();

            // Soft delete da empresa: libera o nome e o CPF/CNPJ para novo
            // cadastro (colunas geradas empresa_ativa/documento_ativo).
            $owner->delete();
        });

        return redirect()->route('admin.owners.index')
            ->with('msg', 'Empresa excluída com sucesso.');
    }

    public function deactivateArenaCourt(Arena $arena, Court $court)
    {
        abort_unless($court->arena_id === $arena->id, 404);

        DB::transaction(function () use ($court) {
            Booking::cancelarEmLote(
                Booking::where('court_id', $court->id)->whereIn('status', ['pending', 'confirmed'])->get(),
                'Quadra desativada pelo administrador geral.',
                auth()->id()
            );

            $court->update(['active' => false]);
        });

        return back()->with('msg', 'Quadra desativada com sucesso.');
    }

    public function activateArenaCourt(Arena $arena, Court $court)
    {
        abort_unless($court->arena_id === $arena->id, 404);

        if (! $arena->active) {
            if (request()->ajax()) {
                return response()->json([
                    'message' => 'Ative a arena antes de ativar esta quadra.',
                ], 422);
            }

            return back()->with('msg', 'Ative a arena antes de ativar esta quadra.');
        }

        $court->update(['active' => true]);

        return back()->with('msg', 'Quadra ativada com sucesso.');
    }

    public function destroyArenaCourt(Arena $arena, Court $court)
    {
        abort_unless($court->arena_id === $arena->id, 404);

        DB::transaction(function () use ($court) {
            Booking::cancelarEmLote(
                Booking::where('court_id', $court->id)->whereIn('status', ['pending', 'confirmed'])->get(),
                'Quadra excluída pelo administrador geral.',
                auth()->id()
            );

            $court->update(['active' => false]);
            $court->delete();
        });

        return back()->with('msg', 'Quadra excluída e histórico preservado.');
    }

    public function arenaDetails(Arena $arena)
    {
        $arena->load([
            'owner.user',
            'paymentMethods',
            'employees.user',
            'employees.createdBy',
            'courts.sports',
            'businessHours' => fn ($query) => $query
                ->orderBy('day_of_week')
                ->orderBy('opens_at'),
        ])->loadCount(['courts', 'employees']);

        // Faturamento REAL da arena (via caixa): reservas + cancelamentos +
        // entradas avulsas − despesas. Ver App\Support\Faturamento.
        $anoFaturamento = (int) request('ano_faturamento', now()->year);
        $anosFaturamento = \App\Support\Faturamento::anos([$arena->id]);
        if (! $anosFaturamento->contains($anoFaturamento)) {
            $anoFaturamento = (int) now()->year;
        }

        $fatMes = \App\Support\Faturamento::resumo(
            [$arena->id],
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );
        $fatAcumulado = \App\Support\Faturamento::resumo([$arena->id]);
        $fatAno = \App\Support\Faturamento::resumo(
            [$arena->id],
            $anoFaturamento . '-01-01',
            $anoFaturamento . '-12-31'
        );
        $fatMensal = \App\Support\Faturamento::mensal([$arena->id], $anoFaturamento);

        $quadrasAtivas = $arena->courts()->where('active', true)->count();
        $funcionariosAtivos = $arena->employees()->where('active', true)->count();
        $idsQuadras = $arena->courts()->withTrashed()->select('id');
        $reservasTotal = Booking::whereIn('court_id', clone $idsQuadras)->count();
        $reservasMes = Booking::whereIn('court_id', clone $idsQuadras)
            ->whereBetween('date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->count();

        // Mesma ordem escolhida no controle; padrão número crescente, para a
        // coluna Nº não embaralhar (ver Booking::scopeOrdenado).
        $ordenarReserva = Booking::ordemValida(request('ordenar_reserva'));

        $consultaReservas = Booking::with([
            'courtWithTrashed',
            'client.user',
            'cancelledBy',
            'payments.paymentMethod',
        ])
            ->whereIn('court_id', clone $idsQuadras)
            ->ordenado($ordenarReserva);

        $buscaReserva = trim((string) request('busca_reserva'));
        $chaveBuscaReserva = preg_replace('/\s+/u', '', mb_strtolower($buscaReserva));
        $dataReserva = null;

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $buscaReserva, $partesData)
            && checkdate((int) $partesData[2], (int) $partesData[1], (int) $partesData[3])) {
            $dataReserva = sprintf('%04d-%02d-%02d', $partesData[3], $partesData[2], $partesData[1]);
        }

        $consultaReservas
            ->when($chaveBuscaReserva !== '' && ! $dataReserva, function ($query) use ($chaveBuscaReserva) {
                $termo = '%' . $chaveBuscaReserva . '%';

                $query->whereHas('client.user', function ($user) use ($termo) {
                    $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo]);
                });
            })
            ->when($dataReserva, fn ($query) => $query->whereDate('date', $dataReserva));

        $filtrosReservas = [
            'reservas_modal' => 1,
            'busca_reserva' => $buscaReserva,
            'ordenar_reserva' => $ordenarReserva,
        ];

        $reservasMesLista = (clone $consultaReservas)
            ->whereBetween('date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->paginate(25, ['*'], 'mes_page')
            ->appends($filtrosReservas + ['aba_reservas' => 'mes']);

        $reservasCanceladasLista = (clone $consultaReservas)
            ->where('status', 'cancelled')
            ->paginate(25, ['*'], 'canceladas_page')
            ->appends($filtrosReservas + ['aba_reservas' => 'canceladas']);

        $historicoReservasLista = (clone $consultaReservas)
            ->paginate(25, ['*'], 'historico_page')
            ->appends($filtrosReservas + ['aba_reservas' => 'historico']);

        $buscaCliente = trim((string) request('busca_cliente'));
        $chaveBuscaCliente = preg_replace('/\s+/u', '', mb_strtolower($buscaCliente));

        $clientesArena = Client::with('user')
            ->whereIn(
                'id',
                Booking::select('client_id')
                    ->whereIn('court_id', clone $idsQuadras)
                    ->distinct()
            )
            ->when($chaveBuscaCliente !== '', function ($query) use ($chaveBuscaCliente) {
                $termo = '%' . $chaveBuscaCliente . '%';

                $query->whereHas('user', function ($user) use ($termo) {
                    $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(email), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(COALESCE(phone, '')), ' ', '') LIKE ?", [$termo]);
                });
            })
            ->select('clients.*')
            ->selectSub(
                Booking::selectRaw('COUNT(*)')
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras),
                'reservas_na_arena'
            )
            ->selectSub(
                // Total gasto = o que o cliente REALMENTE pagou (payments 'paid'
                // já líquidos, menos reembolsos). A fórmula antiga somava o valor
                // de toda reserva não cancelada, inclusive as não pagas, inflando
                // o gasto com dívida. Ver clientesComReservas.
                Booking::selectRaw("COALESCE(SUM(COALESCE((SELECT SUM(pg.amount) FROM payments pg WHERE pg.booking_id = bookings.id AND pg.status = 'paid'), 0) - COALESCE((SELECT SUM(pr.refund_amount) FROM payments pr WHERE pr.booking_id = bookings.id AND pr.refunded_at IS NOT NULL), 0)), 0)")
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras),
                'valor_total_na_arena'
            )
            ->orderByDesc('reservas_na_arena')
            ->orderBy(
                User::select('name')->whereColumn('users.id', 'clients.user_id')
            )
            ->paginate(25, ['*'], 'clientes_page')
            ->appends([
                'clientes_modal' => 1,
                'busca_cliente' => $buscaCliente,
            ]);

        return view('admin.arenas.show', compact(
            'arena',
            'anoFaturamento',
            'anosFaturamento',
            'fatMes',
            'fatAcumulado',
            'fatAno',
            'fatMensal',
            'quadrasAtivas',
            'funcionariosAtivos',
            'reservasTotal',
            'reservasMes',
            'reservasMesLista',
            'reservasCanceladasLista',
            'historicoReservasLista',
            'clientesArena'
        ));
    }

    public function arenaClients(Arena $arena)
    {
        $idsQuadras = $arena->courts()->withTrashed()->select('id');
        $busca = trim((string) request('busca_cliente'));
        $chave = preg_replace('/\s+/u', '', mb_strtolower($busca));

        $clientes = Client::with('user')
            ->whereIn('id', Booking::select('client_id')
                ->whereIn('court_id', clone $idsQuadras)->distinct())
            ->when($chave !== '', function ($query) use ($chave) {
                $termo = '%' . $chave . '%';
                $query->whereHas('user', function ($user) use ($termo) {
                    $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(email), ' ', '') LIKE ?", [$termo])
                        ->orWhereRaw("REPLACE(LOWER(COALESCE(phone, '')), ' ', '') LIKE ?", [$termo]);
                });
            })
            ->select('clients.*')
            ->selectSub(
                Booking::selectRaw('COUNT(*)')
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras),
                'reservas_total'
            )
            ->selectSub(
                // Total gasto = o que o cliente REALMENTE pagou (payments 'paid'
                // já líquidos, menos reembolsos). A fórmula antiga somava o valor
                // de toda reserva não cancelada, inclusive as não pagas, inflando
                // o gasto com dívida. Ver clientesComReservas.
                Booking::selectRaw("COALESCE(SUM(COALESCE((SELECT SUM(pg.amount) FROM payments pg WHERE pg.booking_id = bookings.id AND pg.status = 'paid'), 0) - COALESCE((SELECT SUM(pr.refund_amount) FROM payments pr WHERE pr.booking_id = bookings.id AND pr.refunded_at IS NOT NULL), 0)), 0)")
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras),
                'valor_total'
            )
            ->orderByDesc('reservas_total')
            ->orderBy(User::select('name')->whereColumn('users.id', 'clients.user_id'))
            ->simplePaginate(25)
            ->appends(['busca_cliente' => $busca]);

        return response()->json([
            'html' => view('admin.clients._rows', compact('clientes'))->render(),
            'next_url' => $clientes->nextPageUrl(),
        ]);
    }

    public function destroyArenaEmployee(Arena $arena, Employee $employee)
    {
        abort_unless($employee->arena_id === $arena->id, 404);

        DB::transaction(function () use ($employee) {
            // Encerra a conta (anonimiza, libera o e-mail, derruba a sessão).
            // O nome vira "Gerente/Atendente removido #id": a FUNÇÃO fica para a
            // auditoria do caixa, o dado pessoal some.
            $nome = $employee->user?->name ?? 'Funcionário';
            $employee->user?->encerrarConta();

            $employee->delete();

            // O dono precisa saber que perdeu um funcionário da equipe dele.
            if ($employee->arena) {
                UserNotification::paraDonoDaArena(
                    $employee->arena,
                    'Funcionário removido pelo administrador',
                    'O funcionário "'.$nome.'" foi removido da arena "'.$employee->arena->name.'" '
                        .'pelo administrador do sistema. O histórico do que ele registrou foi preservado.',
                    auth()->id()
                );
            }
        });

        return back()->with('msg', 'Funcionário excluído. O histórico foi preservado e o e-mail liberado para um novo cadastro.');
    }

    public function deactivateArena(Arena $arena)
    {
        DB::transaction(function () use ($arena) {
            $courtIds = $arena->courts()->withTrashed()->pluck('id');

            Booking::cancelarEmLote(
                Booking::whereIn('court_id', $courtIds)->whereIn('status', ['pending', 'confirmed'])->get(),
                'Arena desativada pelo administrador geral.',
                auth()->id()
            );

            // Fecha o caixa que estiver aberto, apurando o saldo. Sem isto a
            // arena ficava suspensa com um caixa em aberto, e a equipe seguia
            // lançando movimento nele. Mesma regra do dono (ArenaController).
            ArenaController::fecharCaixaAbertoDaArena($arena);

            $arena->courts()->update(['active' => false]);
            $arena->update([
                'active' => false,
                'deactivated_by_admin' => true,
            ]);

            // O dono precisa saber: não foi ele quem desativou, e ele não
            // consegue reativar sozinho (deactivated_by_admin).
            UserNotification::paraDonoDaArena(
                $arena,
                'Arena desativada pelo administrador',
                'A arena "'.$arena->name.'" foi desativada pelo administrador do sistema. '
                    .'As reservas ativas foram canceladas e só o administrador pode reativá-la.',
                auth()->id()
            );
        });

        return back()->with('msg', 'Arena desativada e reservas ativas canceladas.');
    }

    public function activateArena(Arena $arena)
    {
        DB::transaction(function () use ($arena) {
            $arena->update([
                'active' => true,
                'deactivated_by_admin' => false,
            ]);
            $arena->courts()->update(['active' => true]);
        });

        return back()->with('msg', 'Arena e suas quadras ativadas com sucesso.');
    }

    public function destroyArena(Arena $arena)
    {
        $owner = $arena->owner;

        DB::transaction(function () use ($arena) {
            $courtIds = $arena->courts()->withTrashed()->pluck('id');

            Booking::cancelarEmLote(
                Booking::whereIn('court_id', $courtIds)->whereIn('status', ['pending', 'confirmed'])->get(),
                'Arena excluída pelo administrador geral.',
                auth()->id()
            );

            // Fecha o caixa aberto antes de excluir, apurando o saldo, e encerra
            // os funcionários da arena — mesmas regras que o dono já aplica.
            ArenaController::fecharCaixaAbertoDaArena($arena);
            ArenaController::encerrarFuncionariosDaArena($arena);

            // Avisa o dono ANTES de excluir, enquanto o vínculo ainda existe.
            UserNotification::paraDonoDaArena(
                $arena,
                'Arena excluída pelo administrador',
                'A arena "'.$arena->name.'" foi excluída pelo administrador do sistema. '
                    .'As reservas ativas foram canceladas e os vínculos dos funcionários dela foram encerrados. '
                    .'O histórico e o caixa continuam disponíveis.',
                auth()->id()
            );

            $arena->courts()->update(['active' => false]);
            $arena->update(['active' => false]);
            $arena->anonimizarContato(); // telefone/e-mail somem; nome e endereço ficam
            $arena->delete();
        });

        return redirect()->route('admin.owners.show', [$owner, 'arenas_modal' => 1])
            ->with('msg', 'Arena excluída com sucesso. O histórico foi preservado.');
    }
}
