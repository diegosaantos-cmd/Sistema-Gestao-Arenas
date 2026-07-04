<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Court;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $arenas = Arena::withTrashed()
            ->with('owner.user')
            ->withCount(['courts', 'employees'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($arena) use ($faturamentoPorArena) {
                $arena->faturamento_mes = (float) ($faturamentoPorArena[$arena->id] ?? 0);

                return $arena;
            });

        $faturamentoBruto = $arenas->sum('faturamento_mes');

        $resumo = [
            'proprietarios' => Owner::count(),
            'arenas' => Arena::count(),
            'arenas_ativas' => Arena::where('active', true)->count(),
            'quadras' => Court::count(),
            'clientes' => Client::count(),
            'funcionarios' => Employee::count(),
            'reservas_mes' => Booking::whereBetween('date', [
                $inicioMes->toDateString(),
                $fimMes->toDateString(),
            ])->count(),
            'faturamento_bruto' => $faturamentoBruto,
        ];

        $proprietarios = Owner::with('user')
            ->withCount('arenas')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $clientes = User::where('type', 'client')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $reservasRecentes = Booking::with(['court.arena', 'client.user'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact(
            'arenas',
            'clientes',
            'proprietarios',
            'reservasRecentes',
            'resumo'
        ));
    }

    public function owners()
    {
        $proprietarios = Owner::with(['user', 'deactivatedBy'])
            ->withCount('arenas')
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

        $reservasMesLista = (clone $consultaReservas)
            ->whereBetween('date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->paginate(25, ['*'], 'mes_page')
            ->appends(['reservas_modal' => 1, 'aba_reservas' => 'mes']);

        $reservasCanceladasLista = (clone $consultaReservas)
            ->where('status', 'cancelled')
            ->paginate(25, ['*'], 'canceladas_page')
            ->appends(['reservas_modal' => 1, 'aba_reservas' => 'canceladas']);

        $historicoReservasLista = (clone $consultaReservas)
            ->paginate(25, ['*'], 'historico_page')
            ->appends(['reservas_modal' => 1, 'aba_reservas' => 'historico']);

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
