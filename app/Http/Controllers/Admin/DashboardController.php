<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Court;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\SystemAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
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
            'clientes' => Client::count(),
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

        return view('admin.dashboard', compact(
            'resumo'
        ));
    }

    /**
     * Lista todos os usuários do sistema (exceto administradores), com seus
     * dados — sem a senha. Alimenta a tela aberta pelo card "Total de usuários".
     */
    public function usuarios()
    {
        $q = trim((string) request('q'));

        $usuarios = User::where('type', '!=', 'admin')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.system.usuarios', compact('usuarios'));
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

        $reservas = collect();
        if ($user->client) {
            $reservas = Booking::with(['court.arena'])
                ->where('client_id', $user->client->id)
                ->orderByDesc('date')->orderByDesc('start_time')
                ->paginate(20);
        }

        return view('admin.users.show', compact('user', 'reservas'));
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
                Booking::selectRaw('COALESCE(SUM(total_amount), 0)')->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras)->where('status', '!=', 'cancelled'),
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

        $consulta = Booking::with(['courtWithTrashed', 'client.user', 'cancelledBy', 'payments.paymentMethod'])
            ->whereIn('court_id', clone $idsQuadras)
            ->orderByDesc('date')->orderByDesc('start_time');

        $busca = trim((string) request('busca_reserva'));
        $chave = preg_replace('/\s+/u', '', mb_strtolower($busca));
        $data = null;
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $busca, $p) && checkdate((int) $p[2], (int) $p[1], (int) $p[3])) {
            $data = sprintf('%04d-%02d-%02d', $p[3], $p[2], $p[1]);
        }
        $consulta->when($chave !== '' && ! $data, function ($query) use ($chave) {
            $termo = '%' . $chave . '%';
            $query->whereHas('client.user', fn ($u) => $u->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo]));
        })->when($data, fn ($query) => $query->whereDate('date', $data));

        $aba = request('aba_reservas', 'mes');
        $filtros = ['busca_reserva' => $busca, 'aba_reservas' => $aba];

        $reservasMesLista = (clone $consulta)
            ->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->paginate(25, ['*'], 'mes_page')->appends($filtros);
        $reservasCanceladasLista = (clone $consulta)->where('status', 'cancelled')
            ->paginate(25, ['*'], 'canceladas_page')->appends($filtros);
        $historicoReservasLista = (clone $consulta)
            ->paginate(25, ['*'], 'historico_page')->appends($filtros);

        return view('admin.arenas.reservas-page', compact(
            'arena', 'aba', 'busca',
            'reservasMesLista', 'reservasCanceladasLista', 'historicoReservasLista'
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
            return back()->with('msg', 'A senha informada está incorreta.');
        }

        DB::transaction(function () use ($admin) {
            $admin->update(['active' => false]);
            $admin->systemAdmin?->delete();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $admin->id)->delete();
            }

            if (Schema::hasTable('personal_access_tokens')) {
                $admin->tokens()->delete();
            }

            $admin->delete();
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

        return view('admin.system.users', compact('usuarios'));
    }

    public function systemClientsData()
    {
        $usuarios = $this->systemClientsQuery()
            ->simplePaginate(25)
            ->appends(['busca_cliente' => request('busca_cliente')]);

        return response()->json([
            'html' => view('admin.system._client-rows', compact('usuarios'))->render(),
            'next_url' => $usuarios->nextPageUrl(),
        ]);
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

        DB::transaction(function () use ($user) {
            $user->update(['active' => false]);

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

        DB::transaction(function () use ($user) {
            $user->update(['active' => false]);
            $employee = $user->employee;
            $employee?->update(['active' => false]);
            $user->systemAdmin?->delete();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('personal_access_tokens')) {
                $user->tokens()->delete();
            }

            $employee?->delete();
            $user->delete();
        });

        return back()->with('msg', 'Usuário excluído com sucesso. O histórico foi preservado.');
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

        $faturamentoPorArena = DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('courts', 'courts.id', '=', 'bookings.court_id')
            ->where('payments.status', 'paid')
            ->whereBetween('payments.paid_at', [$inicioMes, $fimMes])
            ->whereIn('courts.arena_id', $owner->arenas()->select('arenas.id'))
            ->groupBy('courts.arena_id')
            ->selectRaw('courts.arena_id, COALESCE(SUM(payments.amount), 0) as total')
            ->pluck('total', 'courts.arena_id');

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

        $faturamentoHistoricoEmpresa = DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('courts', 'courts.id', '=', 'bookings.court_id')
            ->join('arenas', 'arenas.id', '=', 'courts.arena_id')
            ->where('payments.status', 'paid')
            ->whereIn('courts.arena_id', $owner->arenas()->select('arenas.id'))
            ->groupBy(
                'courts.arena_id',
                'arenas.name',
                DB::raw("DATE_FORMAT(COALESCE(payments.paid_at, payments.created_at), '%Y-%m')")
            )
            ->orderByRaw("DATE_FORMAT(COALESCE(payments.paid_at, payments.created_at), '%Y-%m') DESC")
            ->orderBy('arenas.name')
            ->selectRaw("
                courts.arena_id,
                arenas.name as arena_nome,
                DATE_FORMAT(COALESCE(payments.paid_at, payments.created_at), '%Y-%m') as mes,
                SUM(payments.amount) as total
            ")
            ->get();

        $faturamentoAcumuladoEmpresa = (float) $faturamentoHistoricoEmpresa->sum('total');
        $anosFaturamentoEmpresa = $faturamentoHistoricoEmpresa
            ->map(fn ($registro) => (int) substr($registro->mes, 0, 4))
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $anoFaturamentoEmpresa = (int) request('ano_faturamento', now()->year);
        if (! $anosFaturamentoEmpresa->contains($anoFaturamentoEmpresa)) {
            $anoFaturamentoEmpresa = (int) now()->year;
        }

        $faturamentoAnoEmpresa = $faturamentoHistoricoEmpresa
            ->filter(fn ($registro) =>
                (int) substr($registro->mes, 0, 4) === $anoFaturamentoEmpresa
            )
            ->values();

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
                Booking::selectRaw('COALESCE(SUM(total_amount), 0)')
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadrasEmpresa)
                    ->where('status', '!=', 'cancelled'),
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
            'faturamentoAcumuladoEmpresa',
            'anosFaturamentoEmpresa',
            'anoFaturamentoEmpresa',
            'faturamentoAnoEmpresa'
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
                Booking::selectRaw('COALESCE(SUM(total_amount), 0)')
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras)
                    ->where('status', '!=', 'cancelled'),
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
            ->with('msg', 'Empresa desativada com sucesso.');
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
            $owner->arenas()->update(['active' => false]);
            Court::whereIn('arena_id', $owner->arenas()->select('arenas.id'))
                ->update(['active' => false]);

            foreach ($owner->arenas as $arena) {
                $arena->delete();
            }

            if ($owner->user) {
                DB::table('sessions')->where('user_id', $owner->user->id)->delete();
                if (Schema::hasTable('personal_access_tokens')) {
                    $owner->user->tokens()->delete();
                }
                $owner->user->update(['active' => false]);
            }

            $owner->delete();
            $owner->user?->delete();
        });

        return redirect()->route('admin.owners.index')
            ->with('msg', 'Empresa excluída com sucesso.');
    }

    public function deactivateArenaCourt(Arena $arena, Court $court)
    {
        abort_unless($court->arena_id === $arena->id, 404);

        DB::transaction(function () use ($court) {
            Booking::where('court_id', $court->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Quadra desativada pelo administrador geral.',
                ]);

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
            Booking::where('court_id', $court->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Quadra excluída pelo administrador geral.',
                ]);

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

        $faturamentoMensalCompleto = DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('courts', 'courts.id', '=', 'bookings.court_id')
            ->where('courts.arena_id', $arena->id)
            ->where('payments.status', 'paid')
            ->groupByRaw("DATE_FORMAT(COALESCE(payments.paid_at, payments.created_at), '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(COALESCE(payments.paid_at, payments.created_at), '%Y-%m') DESC")
            ->selectRaw("
                DATE_FORMAT(COALESCE(payments.paid_at, payments.created_at), '%Y-%m') as mes,
                SUM(payments.amount) as total
            ")
            ->get();

        $faturamentoTotal = (float) $faturamentoMensalCompleto->sum('total');
        $faturamentoMesAtual = (float) optional(
            $faturamentoMensalCompleto->firstWhere('mes', now()->format('Y-m'))
        )->total;

        $anosFaturamento = $faturamentoMensalCompleto
            ->map(fn ($registro) => (int) substr($registro->mes, 0, 4))
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $anoFaturamento = (int) request('ano_faturamento', now()->year);
        if (! $anosFaturamento->contains($anoFaturamento)) {
            $anoFaturamento = (int) now()->year;
        }

        $faturamentoMensal = $faturamentoMensalCompleto
            ->filter(fn ($registro) => (int) substr($registro->mes, 0, 4) === $anoFaturamento)
            ->values();

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

        $consultaReservas = Booking::with([
            'courtWithTrashed',
            'client.user',
            'cancelledBy',
            'payments.paymentMethod',
        ])
            ->whereIn('court_id', clone $idsQuadras)
            ->orderByDesc('date')
            ->orderByDesc('start_time');

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
                Booking::selectRaw('COALESCE(SUM(total_amount), 0)')
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras)
                    ->where('status', '!=', 'cancelled'),
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
            'faturamentoMensal',
            'anosFaturamento',
            'anoFaturamento',
            'faturamentoTotal',
            'faturamentoMesAtual',
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
                Booking::selectRaw('COALESCE(SUM(total_amount), 0)')
                    ->whereColumn('bookings.client_id', 'clients.id')
                    ->whereIn('court_id', clone $idsQuadras)
                    ->where('status', '!=', 'cancelled'),
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
            $user = $employee->user;
            $employee->delete();
            $user?->delete();
        });

        return back()->with('msg', 'Funcionário excluído com sucesso.');
    }

    public function deactivateArena(Arena $arena)
    {
        DB::transaction(function () use ($arena) {
            $courtIds = $arena->courts()->pluck('id');

            Booking::whereIn('court_id', $courtIds)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Arena desativada pelo administrador geral.',
                ]);

            $arena->courts()->update(['active' => false]);
            $arena->update([
                'active' => false,
                'deactivated_by_admin' => true,
            ]);
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
            $courtIds = $arena->courts()->pluck('id');

            Booking::whereIn('court_id', $courtIds)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Arena excluída pelo administrador geral.',
                ]);

            $arena->courts()->update(['active' => false]);
            $arena->update(['active' => false]);
            $arena->delete();
        });

        return redirect()->route('admin.owners.show', [$owner, 'arenas_modal' => 1])
            ->with('msg', 'Arena excluída com sucesso. O histórico foi preservado.');
    }
}
