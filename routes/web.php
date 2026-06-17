<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArenaController;
use App\Http\Controllers\QuadraController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ClientController;
use App\Models\Arena;
use App\Http\Controllers\OwnersController;
use App\Http\Controllers\RegisterArenaOwnerController;

Route::get('/', function () {
    $arenas = Arena::all();
    return view('welcome', compact('arenas'));
});

Route::get('/registerArenaOwners', [RegisterArenaOwnerController::class, 'create'])
    ->name('register.arena.owners');

Route::post('/registerArenaOwners', [RegisterArenaOwnerController::class, 'store'])
    ->name('register.arena.owners.store');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {

        if (auth()->user()->type === 'owner') {
            return redirect()->route('owners.dashboard');
        }

        if (auth()->user()->type === 'employee') {
            return redirect()->route('employees.dashboard');
        }
        return view('dashboard');

    })->name('dashboard');
    Route::get('/employees/dashboard', function () {
        $employee = \App\Models\Employee::where('user_id', auth()->id())->first();
        $arena = $employee?->arena;

        return view('employees.dashboard', compact('employee', 'arena'));
    })->name('employees.dashboard');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard');
    });
});

Route::middleware('auth')->group(function () {

    Route::get('/owners/create', [OwnersController::class, 'create'])
        ->name('owners.create');

    Route::post('/owners', [OwnersController::class, 'store'])
        ->name('owners.store');

});

Route::middleware(['auth'])->group(function () {
    Route::get('/owners/dashboard', function () {
        $owner = \App\Models\Owner::where('user_id', auth()->id())->first();

        $arenas = $owner ? $owner->arenas()->orderBy('name')->get() : collect();
        $arenasCount = $arenas->count();

        // Arena que ele está gerenciando agora (guardada na sessão).
        $selectedArena = $arenas->firstWhere('id', session('selected_arena_id'));

        // Tem mais de uma e ainda não escolheu -> pergunta em qual entrar.
        if (! $selectedArena && $arenasCount > 1) {
            return redirect()->route('owners.arena.choose');
        }

        // Só tem uma -> entra direto nela.
        if (! $selectedArena && $arenasCount === 1) {
            $selectedArena = $arenas->first();
        }

        if ($selectedArena) {
            session(['selected_arena_id' => $selectedArena->id]);
        }

        // Dados abaixo são SÓ da arena selecionada (o card Arenas continua sendo o total).
        $courtsCount = $selectedArena ? $selectedArena->courts()->count() : 0;

        // Clientes = clientes distintos com reserva nas quadras desta arena
        // (qualquer status, inclusive cancelada — quem reservou virou cliente).
        $customersCount = $selectedArena
            ? \App\Models\Booking::whereIn('court_id', $selectedArena->courts()->select('id'))
                ->distinct('client_id')
                ->count('client_id')
            : 0;

        // Funcionários desta arena.
        $employeesCount = $selectedArena
            ? \App\Models\Employee::where('arena_id', $selectedArena->id)->count()
            : 0;

        // Agendamentos de hoje desta arena (ignora cancelados).
        $agendamentosHoje = $selectedArena
            ? \App\Models\Booking::whereIn('court_id', $selectedArena->courts()->select('id'))
                ->whereDate('date', now()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->count()
            : 0;

        // Próximos agendamentos desta arena (a partir de hoje).
        $proximosAgendamentos = $selectedArena
            ? \App\Models\Booking::with(['court', 'client.user'])
                ->whereIn('court_id', $selectedArena->courts()->select('id'))
                ->whereDate('date', '>=', now()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('owners.dashboard', compact(
            'arenas', 'arenasCount', 'selectedArena',
            'courtsCount', 'customersCount', 'agendamentosHoje',
            'employeesCount', 'proximosAgendamentos'
        ));
    })->name('owners.dashboard');

    // Tela "em qual arena entrar" (quando há mais de uma).
    Route::get('/owners/arena/choose', function () {
        $owner = \App\Models\Owner::where('user_id', auth()->id())->first();
        $arenas = $owner ? $owner->arenas()->orderBy('name')->get() : collect();

        if ($arenas->isEmpty()) {
            return redirect()->route('arenas.create');
        }

        // Uma só não precisa escolher.
        if ($arenas->count() === 1) {
            session(['selected_arena_id' => $arenas->first()->id]);
            return redirect()->route('owners.dashboard');
        }

        return view('owners.choose-arena', compact('arenas'));
    })->name('owners.arena.choose');

    // Define a arena selecionada (valida que pertence ao dono).
    Route::post('/owners/arena/select', function (\Illuminate\Http\Request $request) {
        $owner = \App\Models\Owner::where('user_id', auth()->id())->first();

        $arena = $owner ? $owner->arenas()->find($request->input('arena_id')) : null;

        if ($arena) {
            session(['selected_arena_id' => $arena->id]);
        }

        return redirect()->route('owners.dashboard');
    })->name('owners.arena.select');

    // Lista de clientes da arena atual.
    Route::get('/owners/clients', [ClientController::class, 'index'])
        ->name('clients.index');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('arenas', ArenaController::class);
    Route::resource('quadras', QuadraController::class);
    Route::resource('employees', EmployeeController::class);
});

