<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArenaController;
use App\Http\Controllers\QuadraController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Client\ArenaController as ClientArenaController;
use App\Http\Controllers\Client\BookingController as ClientBookingController;
use App\Http\Controllers\Client\ProfileController as ClientProfileController;
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

        // Confirma automaticamente as reservas pendentes cujo prazo expirou.
        \App\Models\Booking::autoConfirmarExpiradas();

        // Cliente: resumo das próprias reservas.
        $client = \App\Models\Client::where('user_id', auth()->id())->first();
        $hoje = now()->toDateString();

        $pendentes = 0;
        $confirmados = 0;
        $hojeCount = 0;
        $proximos = collect();

        if ($client) {
            $pendentes = \App\Models\Booking::where('client_id', $client->id)
                ->whereDate('date', '>=', $hoje)
                ->where('status', 'pending')
                ->count();

            $confirmados = \App\Models\Booking::where('client_id', $client->id)
                ->whereDate('date', '>=', $hoje)
                ->where('status', 'confirmed')
                ->count();

            // Agendamentos de hoje (não cancelados).
            $hojeCount = \App\Models\Booking::where('client_id', $client->id)
                ->whereDate('date', $hoje)
                ->where('status', '!=', 'cancelled')
                ->count();

            $proximos = \App\Models\Booking::where('client_id', $client->id)
                ->whereDate('date', '>=', $hoje)
                ->whereIn('status', ['pending', 'confirmed'])
                ->with('court.arena')
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(4)
                ->get();
        }

        $proximosCount = $pendentes + $confirmados;

        return view('dashboard', compact('pendentes', 'confirmados', 'hojeCount', 'proximosCount', 'proximos'));

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
        $arenasActive = $arenas->where('active', true)->count();

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

            // Confirma automaticamente as reservas pendentes cujo prazo expirou.
            \App\Models\Booking::autoConfirmarExpiradas(
                $selectedArena->courts()->pluck('id')->all()
            );
        }

        // Dados abaixo são SÓ da arena selecionada (o card Arenas continua sendo o total).
        $courtsCount = $selectedArena ? $selectedArena->courts()->count() : 0;
        $courtsActive = $selectedArena ? $selectedArena->courts()->where('active', true)->count() : 0;

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
        $employeesActive = $selectedArena
            ? \App\Models\Employee::where('arena_id', $selectedArena->id)->where('active', true)->count()
            : 0;

        // Reservas de hoje desta arena: só as confirmadas (as que vão acontecer).
        $agendamentosHoje = $selectedArena
            ? \App\Models\Booking::whereIn('court_id', $selectedArena->courts()->select('id'))
                ->whereDate('date', now()->toDateString())
                ->where('status', 'confirmed')
                ->count()
            : 0;

        // Próximos agendamentos desta arena: preview de 4 + total (badge / "Ver todos").
        $proximosAgendamentos = collect();
        $proximosCount = 0;

        if ($selectedArena) {
            $base = \App\Models\Booking::whereIn('court_id', $selectedArena->courts()->select('id'))
                ->whereDate('date', '>=', now()->toDateString())
                ->where('status', '!=', 'cancelled');

            $proximosCount = (clone $base)->count();

            $proximosAgendamentos = $base->with(['court', 'client.user'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(4)
                ->get();
        }

        return view('owners.dashboard', compact(
            'arenas', 'arenasCount', 'arenasActive', 'selectedArena',
            'courtsCount', 'courtsActive', 'customersCount', 'agendamentosHoje',
            'employeesCount', 'employeesActive', 'proximosAgendamentos', 'proximosCount'
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

    // Todos os próximos agendamentos da arena atual.
    Route::get('/owners/bookings', [BookingController::class, 'index'])
        ->name('bookings.index');

    // Histórico de agendamentos da arena atual.
    Route::get('/owners/bookings/history', [BookingController::class, 'history'])
        ->name('bookings.history');

    // Reservas de hoje (só confirmadas).
    Route::get('/owners/bookings/today', [BookingController::class, 'today'])
        ->name('bookings.today');
});

// Área do cliente — navegar nas arenas.
Route::middleware(['auth'])->group(function () {
    Route::get('/client/arenas', [ClientArenaController::class, 'index'])
        ->name('client.arenas.index');

    Route::get('/client/arenas/{arena}', [ClientArenaController::class, 'show'])
        ->name('client.arenas.show');

    // Reservar uma quadra.
    Route::get('/client/arenas/{arena}/quadras/{court}/reservar', [ClientBookingController::class, 'create'])
        ->name('client.bookings.create');

    Route::post('/client/arenas/{arena}/quadras/{court}/reservar', [ClientBookingController::class, 'store'])
        ->name('client.bookings.store');

    Route::get('/client/reserva-confirmada', function () {
        return view('client.bookings.success');
    })->name('client.bookings.success');

    // Próximos agendamentos, histórico e cancelar.
    Route::get('/client/reservas', [ClientBookingController::class, 'index'])
        ->name('client.bookings.index');

    Route::get('/client/reservas/historico', [ClientBookingController::class, 'history'])
        ->name('client.bookings.history');

    Route::patch('/client/reservas/{booking}/cancelar', [ClientBookingController::class, 'cancel'])
        ->name('client.bookings.cancel');

    // Perfil do cliente.
    Route::get('/client/perfil', [ClientProfileController::class, 'edit'])
        ->name('client.profile.edit');

    Route::patch('/client/perfil', [ClientProfileController::class, 'update'])
        ->name('client.profile.update');

    Route::put('/client/perfil/senha', [ClientProfileController::class, 'updatePassword'])
        ->name('client.profile.password');
});

Route::middleware(['auth'])->group(function () {
    Route::patch('/arenas/{arena}/pagamentos', [ArenaController::class, 'updatePayments'])
        ->name('arenas.payments.update');
    Route::patch('/arenas/{arena}/horarios', [ArenaController::class, 'updateBusinessHours'])
        ->name('arenas.hours.update');
    Route::resource('arenas', ArenaController::class);
    Route::resource('quadras', QuadraController::class);
    Route::resource('employees', EmployeeController::class);
    Route::patch('/employees/{employee}/toggle', [EmployeeController::class, 'toggleActive'])
        ->name('employees.toggle');
});

