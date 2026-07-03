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
        $taxaPlataforma = 10;

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
            ->map(function ($arena) use ($faturamentoPorArena, $taxaPlataforma) {
                $arena->faturamento_mes = (float) ($faturamentoPorArena[$arena->id] ?? 0);
                $arena->taxa_plataforma = round($arena->faturamento_mes * $taxaPlataforma / 100, 2);

                return $arena;
            });

        $faturamentoBruto = $arenas->sum('faturamento_mes');
        $lucroPlataforma = $arenas->sum('taxa_plataforma');

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
            'lucro_plataforma' => $lucroPlataforma,
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
            'resumo',
            'taxaPlataforma'
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
            ->withCount(['courts', 'employees'])
            ->orderBy('name')
            ->get()
            ->map(function ($arena) use ($faturamentoPorArena) {
                $arena->faturamento_mes = (float) ($faturamentoPorArena[$arena->id] ?? 0);

                return $arena;
            });

        $totais = [
            'arenas' => $arenas->count(),
            'arenas_ativas' => $arenas->where('active', true)->count(),
            'quadras' => $arenas->sum('courts_count'),
            'funcionarios' => $arenas->sum('employees_count'),
            'faturamento_mes' => $arenas->sum('faturamento_mes'),
        ];

        $empresas = Owner::orderBy('company_name')->get(['id', 'company_name']);

        return view('admin.owners.show', compact('owner', 'arenas', 'totais', 'empresas'));
    }

    public function ownerProfile(Owner $owner)
    {
        $owner->load([
            'user',
            'deactivatedBy',
            'arenas' => fn ($query) => $query
                ->with('businessHours')
                ->withCount('employees')
                ->orderBy('name'),
        ])->loadCount('arenas');
        $totalQuadras = Court::whereIn(
            'arena_id',
            $owner->arenas()->select('arenas.id')
        )->count();
        $totalFuncionarios = $owner->arenas->sum('employees_count');
        $empresas = Owner::orderBy('company_name')->get(['id', 'company_name']);

        return view('admin.owners.profile', compact(
            'owner',
            'totalQuadras',
            'totalFuncionarios',
            'empresas'
        ));
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

    public function ownerArenas(Owner $owner)
    {
        $owner->load('user');

        $arenas = $owner->arenas()
            ->withCount(['courts', 'employees'])
            ->orderBy('name')
            ->get();
        $empresas = Owner::orderBy('company_name')->get(['id', 'company_name']);

        return view('admin.owners.arenas', compact('owner', 'arenas', 'empresas'));
    }

    public function arenaCourts(Arena $arena)
    {
        $arena->load(['owner.user', 'paymentMethods', 'businessHours']);

        $quadras = $arena->courts()
            ->with('sports')
            ->orderBy('name')
            ->get();

        return view('admin.arenas.courts', compact('arena', 'quadras'));
    }
}
