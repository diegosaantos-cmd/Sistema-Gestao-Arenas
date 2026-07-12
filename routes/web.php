<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArenaController;
use App\Http\Controllers\QuadraController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientNotificationController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Client\ArenaController as ClientArenaController;
use App\Http\Controllers\Client\BookingController as ClientBookingController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\FavoriteController as ClientFavoriteController;
use App\Http\Controllers\Client\ProfileController as ClientProfileController;
use App\Models\Arena;
use App\Http\Controllers\OwnersController;
use App\Http\Controllers\RegisterArenaOwnerController;
use App\Http\Controllers\BookingDetailController;
use App\Http\Controllers\Owner\ProfileController as OwnerProfileController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Owner\CashRegisterController;
use App\Http\Controllers\Owner\PresencialBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HomeSlideController;

Route::get('/', function (\Illuminate\Http\Request $request) {
    $busca = trim((string) $request->query('busca'));

    $arenas = Arena::where('active', true)
        ->pesquisar($busca)
        ->with('owner.user')
        ->withCount([
            'courts as quadras_ativas_count' => fn ($query) => $query->where('active', true),
        ])
        // Sem pesquisa: ordem aleatória a cada carregamento, para não
        // beneficiar nenhuma arena. Com pesquisa: ordem alfabética (previsível).
        ->when(
            $busca === '',
            fn ($query) => $query->inRandomOrder(),
            fn ($query) => $query->orderBy('name'),
        )
        ->get();

    // Fotos/textos do cabeçalho, gerenciados pelo admin em /admin/aparencia.
    $slides = \App\Models\HomeSlide::paraHome();

    // Para o coração do card já aparecer preenchido nas arenas que o cliente
    // logado favoritou. Visitante não autenticado: lista vazia.
    $favoritasIds = [];
    $usuario = auth()->user();

    if ($usuario && $usuario->type === 'client') {
        $cliente = \App\Models\Client::where('user_id', $usuario->id)->first();
        $favoritasIds = $cliente ? $cliente->favoritas()->pluck('arenas.id')->all() : [];
    }

    return view('welcome', compact('arenas', 'busca', 'slides', 'favoritasIds'));
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
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/employees/dashboard', function () {
        $employee = \App\Models\Employee::where('user_id', auth()->id())->first();
        $arena = $employee?->arena;

        $hoje = collect();
        $semana = collect();
        $pendentes = 0;

        if ($arena) {
            $idsQuadras = $arena->courts()->pluck('id')->all();

            if (! empty($idsQuadras)) {
                \App\Models\Booking::autoConfirmarExpiradas($idsQuadras);
                \App\Models\Booking::autoCompletarRealizadas($idsQuadras);

                $hoje = \App\Models\Booking::with(['court', 'client.user'])
                    ->whereIn('court_id', $idsQuadras)
                    ->whereDate('date', now()->toDateString())
                    ->where('status', 'confirmed')
                    ->orderBy('start_time')
                    ->get();

                $semana = \App\Models\Booking::with(['court', 'client.user'])
                    ->whereIn('court_id', $idsQuadras)
                    ->whereBetween('date', [
                        now()->startOfWeek()->toDateString(),
                        now()->endOfWeek()->toDateString(),
                    ])
                    ->where('status', 'confirmed')
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->get();

                $pendentes = \App\Models\Booking::whereIn('court_id', $idsQuadras)
                    ->where('status', 'pending')
                    ->whereDate('date', '>=', now()->toDateString())
                    ->count();
            }
        }

        return view('employees.dashboard', compact('employee', 'arena', 'hoje', 'semana', 'pendentes'));
    })->name('employees.dashboard');

    // "Minha Conta" do funcionário (dados pessoais + senha).
    Route::get('/funcionario/perfil', [EmployeeProfileController::class, 'edit'])
        ->name('employee.profile.edit');
    Route::patch('/funcionario/perfil/pessoais', [EmployeeProfileController::class, 'updatePersonal'])
        ->name('employee.profile.personal');
    Route::put('/funcionario/perfil/senha', [EmployeeProfileController::class, 'updatePassword'])
        ->name('employee.profile.password');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');
    Route::post('/admin/administradores', [AdminDashboardController::class, 'storeAdmin'])
        ->name('admin.administrators.store');
    Route::get('/admin/administradores', [AdminDashboardController::class, 'systemAdmins'])
        ->name('admin.system.administrators');
    Route::patch('/admin/perfil', [AdminDashboardController::class, 'updateAdminProfile'])
        ->name('admin.profile.update');
    Route::put('/admin/perfil/senha', [AdminDashboardController::class, 'updateAdminPassword'])
        ->name('admin.profile.password');
    Route::delete('/admin/perfil', [AdminDashboardController::class, 'destroyAdminAccount'])
        ->name('admin.profile.destroy');
    Route::get('/admin/pesquisa-rapida', [AdminDashboardController::class, 'quickSearch'])
        ->name('admin.quick-search');

    // Aparência da tela inicial (fotos e textos do carrossel).
    Route::get('/admin/aparencia', [HomeSlideController::class, 'index'])
        ->name('admin.aparencia');
    Route::post('/admin/aparencia', [HomeSlideController::class, 'store'])
        ->name('admin.aparencia.store');
    Route::patch('/admin/aparencia/{slide}', [HomeSlideController::class, 'update'])
        ->name('admin.aparencia.update');
    Route::patch('/admin/aparencia/{slide}/situacao', [HomeSlideController::class, 'toggle'])
        ->name('admin.aparencia.toggle');
    Route::patch('/admin/aparencia/{slide}/mover/{direcao}', [HomeSlideController::class, 'move'])
        ->name('admin.aparencia.move');
    Route::delete('/admin/aparencia/{slide}', [HomeSlideController::class, 'destroy'])
        ->name('admin.aparencia.destroy');
    Route::get('/admin/arenas', [AdminDashboardController::class, 'systemArenas'])
        ->name('admin.system.arenas');
    Route::get('/admin/quadras', [AdminDashboardController::class, 'systemCourts'])
        ->name('admin.system.courts');
    Route::get('/admin/funcionarios', [AdminDashboardController::class, 'systemEmployees'])
        ->name('admin.system.employees');
    Route::get('/admin/clientes', [AdminDashboardController::class, 'systemClients'])
        ->name('admin.system.clients');
    Route::get('/admin/clientes/carregar', [AdminDashboardController::class, 'systemClientsData'])
        ->name('admin.system.clients.data');
    Route::get('/admin/usuarios', [AdminDashboardController::class, 'usuarios'])
        ->name('admin.system.users');
    Route::get('/admin/usuarios/{user}', [AdminDashboardController::class, 'showUser'])
        ->name('admin.users.show');
    Route::get('/admin/feedbacks', [FeedbackController::class, 'index'])
        ->name('admin.feedbacks');
    Route::patch('/admin/feedbacks/{feedback}/status', [FeedbackController::class, 'updateStatus'])
        ->name('admin.feedbacks.status');
    Route::patch('/admin/usuarios/{user}/bloquear', [AdminDashboardController::class, 'blockUser'])
        ->name('admin.users.block');
    Route::patch('/admin/usuarios/{user}/desbloquear', [AdminDashboardController::class, 'unblockUser'])
        ->name('admin.users.unblock');
    Route::delete('/admin/usuarios/{user}', [AdminDashboardController::class, 'destroyUser'])
        ->name('admin.users.destroy');
    Route::get('/admin/proprietarios', [AdminDashboardController::class, 'owners'])
        ->name('admin.owners.index');
    Route::get('/admin/proprietarios/{owner}', [AdminDashboardController::class, 'ownerDetails'])
        ->name('admin.owners.show');
    Route::get('/admin/proprietarios/{owner}/clientes', [AdminDashboardController::class, 'ownerClients'])
        ->name('admin.owners.clients');
    Route::get('/admin/proprietarios/{owner}/lista-clientes', [AdminDashboardController::class, 'ownerClientsPage'])
        ->name('admin.owners.clients.page');
    Route::patch('/admin/proprietarios/{owner}/desativar', [AdminDashboardController::class, 'deactivateOwner'])
        ->name('admin.owners.deactivate');
    Route::patch('/admin/proprietarios/{owner}/ativar', [AdminDashboardController::class, 'activateOwner'])
        ->name('admin.owners.activate');
    Route::delete('/admin/proprietarios/{owner}', [AdminDashboardController::class, 'destroyOwner'])
        ->name('admin.owners.destroy');
    Route::get('/admin/arenas/{arena}', [AdminDashboardController::class, 'arenaDetails'])
        ->name('admin.arenas.show');
    Route::get('/admin/arenas/{arena}/clientes', [AdminDashboardController::class, 'arenaClients'])
        ->name('admin.arenas.clients');
    Route::get('/admin/arenas/{arena}/lista-clientes', [AdminDashboardController::class, 'arenaClientsPage'])
        ->name('admin.arenas.clients.page');
    Route::get('/admin/arenas/{arena}/reservas', [AdminDashboardController::class, 'arenaReservasPage'])
        ->name('admin.arenas.reservas');
    Route::patch('/admin/arenas/{arena}/desativar', [AdminDashboardController::class, 'deactivateArena'])
        ->name('admin.arenas.deactivate');
    Route::patch('/admin/arenas/{arena}/ativar', [AdminDashboardController::class, 'activateArena'])
        ->name('admin.arenas.activate');
    Route::delete('/admin/arenas/{arena}', [AdminDashboardController::class, 'destroyArena'])
        ->name('admin.arenas.destroy');
    Route::delete('/admin/arenas/{arena}/funcionarios/{employee}', [AdminDashboardController::class, 'destroyArenaEmployee'])
        ->name('admin.arenas.employees.destroy');
    Route::patch('/admin/arenas/{arena}/quadras/{court}/desativar', [AdminDashboardController::class, 'deactivateArenaCourt'])
        ->name('admin.arenas.courts.deactivate');
    Route::patch('/admin/arenas/{arena}/quadras/{court}/ativar', [AdminDashboardController::class, 'activateArenaCourt'])
        ->name('admin.arenas.courts.activate');
    Route::delete('/admin/arenas/{arena}/quadras/{court}', [AdminDashboardController::class, 'destroyArenaCourt'])
        ->name('admin.arenas.courts.destroy');
});

Route::middleware('auth')->group(function () {

    Route::get('/owners/create', [OwnersController::class, 'create'])
        ->name('owners.create');

    Route::post('/owners', [OwnersController::class, 'store'])
        ->name('owners.store');

    Route::patch('/owners/empresa/desativar', [OwnersController::class, 'deactivateCompany'])
        ->name('owners.company.deactivate');

    Route::patch('/owners/empresa/ativar', [OwnersController::class, 'activateCompany'])
        ->name('owners.company.activate');

});

Route::middleware(['auth'])->group(function () {
    Route::get('/owners/dashboard', function () {
        // O painel é reaproveitado pelo gerente (ver App\Support\ArenaAtual):
        //   - DONO: escolhe/troca entre as arenas dele.
        //   - GERENTE: arena fixa, sem escolher nem trocar.
        $ehGerente = \App\Support\ArenaAtual::ehGerente();
        $ehDono    = \App\Support\ArenaAtual::ehDono();

        // Só dono ou gerente entram aqui.
        if (! $ehDono && ! $ehGerente) {
            return redirect()->route('dashboard');
        }

        if ($ehGerente) {
            $owner = null;
            $selectedArena = \App\Support\ArenaAtual::tentar();

            if (! $selectedArena) {
                return redirect()->route('employees.dashboard');
            }

            session(['selected_arena_id' => $selectedArena->id]);
            $arenas = collect([$selectedArena]);
        } else {
            $owner = \App\Models\Owner::where('user_id', auth()->id())->first();

            $arenas = $owner ? $owner->arenas()->orderBy('name')->get() : collect();

            // Arena que ele está gerenciando agora (guardada na sessão).
            $selectedArena = $arenas->firstWhere('id', session('selected_arena_id'));

            // Tem mais de uma e ainda não escolheu -> pergunta em qual entrar.
            if (! $selectedArena && $arenas->count() > 1) {
                return redirect()->route('owners.arena.choose');
            }

            // Só tem uma -> entra direto nela.
            if (! $selectedArena && $arenas->count() === 1) {
                $selectedArena = $arenas->first();
            }

            if ($selectedArena) {
                session(['selected_arena_id' => $selectedArena->id]);
            }
        }

        $arenasCount = $arenas->count();
        $arenasActive = $arenas->where('active', true)->count();

        if ($selectedArena) {
            // Confirma automaticamente as reservas pendentes cujo prazo expirou
            // e marca como realizadas as confirmadas que já terminaram.
            $idsQuadras = $selectedArena->courts()->pluck('id')->all();
            \App\Models\Booking::autoConfirmarExpiradas($idsQuadras);
            \App\Models\Booking::autoCompletarRealizadas($idsQuadras);
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
        // Só CONFIRMADOS — os pendentes ficam na tela "Aguardando confirmação".
        $proximosAgendamentos = collect();
        $proximosCount = 0;
        $pendentesCount = 0;

        if ($selectedArena) {
            $base = \App\Models\Booking::whereIn('court_id', $selectedArena->courts()->select('id'))
                ->whereDate('date', '>=', now()->toDateString())
                ->where('status', 'confirmed');

            $proximosCount = (clone $base)->count();

            $proximosAgendamentos = $base->with(['court', 'client.user'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(4)
                ->get();

            $pendentesCount = \App\Models\Booking::whereIn('court_id', $selectedArena->courts()->select('id'))
                ->where('status', 'pending')
                ->count();
        }

        // Lucro do mês: todas as entradas menos as saídas lançadas no caixa da
        // arena no mês atual (inclui pagamentos de reservas, receitas avulsas e
        // as despesas).
        $entradasMes = 0;
        $saidasMes = 0;
        if ($selectedArena) {
            $entriesMes = \App\Models\CashRegisterEntry::query()
                ->whereIn('cash_register_id', \App\Models\CashRegister::where('arena_id', $selectedArena->id)->select('id'))
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month);

            $entradasMes = (clone $entriesMes)->where('type', 'income')->sum('amount');
            $saidasMes = (clone $entriesMes)->where('type', 'expense')->sum('amount');
        }
        $lucroMes = $entradasMes - $saidasMes;

        $nomesMes = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];
        $mesAtualLabel = $nomesMes[(int) now()->month] . '/' . now()->year;

        return view('owners.dashboard', compact(
            'owner', 'ehGerente', 'arenas', 'arenasCount', 'arenasActive', 'selectedArena',
            'courtsCount', 'courtsActive', 'customersCount', 'agendamentosHoje',
            'employeesCount', 'employeesActive', 'proximosAgendamentos', 'proximosCount',
            'pendentesCount', 'lucroMes', 'entradasMes', 'saidasMes', 'mesAtualLabel'
        ));
    })->name('owners.dashboard');

    // Minha Conta do dono (dados pessoais + empresa + senha).
    Route::get('/owners/perfil', [OwnerProfileController::class, 'edit'])
        ->name('owner.profile.edit');
    Route::patch('/owners/perfil/pessoais', [OwnerProfileController::class, 'updatePersonal'])
        ->name('owner.profile.personal');
    Route::patch('/owners/perfil/empresa', [OwnerProfileController::class, 'updateCompany'])
        ->name('owner.profile.company');
    Route::put('/owners/perfil/senha', [OwnerProfileController::class, 'updatePassword'])
        ->name('owner.profile.password');

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

    // Disparo de mensagem para vários clientes de uma vez.
    Route::get('/owners/clients/mensagem-massa', [ClientController::class, 'broadcastForm'])
        ->name('clients.broadcast.create');
    Route::post('/owners/clients/mensagem-massa', [ClientController::class, 'broadcast'])
        ->name('clients.broadcast');

    // Detalhes de um cliente (reservas, dívidas) e envio de mensagem.
    Route::get('/owners/clients/{client}', [ClientController::class, 'show'])
        ->name('clients.show');
    Route::get('/owners/clients/{client}/reservas/{tipo}', [ClientController::class, 'bookings'])
        ->name('clients.bookings');
    Route::get('/owners/clients/{client}/mensagem', [ClientController::class, 'messageForm'])
        ->name('clients.message.create');
    Route::post('/owners/clients/{client}/mensagem', [ClientController::class, 'sendMessage'])
        ->name('clients.message');

    // Todos os próximos agendamentos da arena atual.
    Route::get('/owners/bookings', [BookingController::class, 'index'])
        ->name('bookings.index');

    // Histórico de agendamentos da arena atual.
    Route::get('/owners/bookings/history', [BookingController::class, 'history'])
        ->name('bookings.history');

    // Reservas de hoje (só confirmadas).
    Route::get('/owners/bookings/today', [BookingController::class, 'today'])
        ->name('bookings.today');

    // Reserva registrada no balcão da arena (cliente que chega na hora).
    Route::get('/owners/bookings/presencial', [PresencialBookingController::class, 'create'])
        ->name('bookings.presencial.create');
    Route::post('/owners/bookings/presencial', [PresencialBookingController::class, 'store'])
        ->name('bookings.presencial.store');

    // Reservas aguardando confirmação (pendentes) + ações do dono.
    Route::get('/owners/bookings/pendentes', [BookingController::class, 'pending'])
        ->name('bookings.pending');
    Route::patch('/owners/bookings/{booking}/confirmar', [BookingController::class, 'confirm'])
        ->name('bookings.confirm');
    Route::patch('/owners/bookings/{booking}/cancelar', [BookingController::class, 'cancel'])
        ->name('bookings.cancel');
    Route::get('/owners/bookings/{booking}/reagendar', [BookingController::class, 'editSchedule'])
        ->name('bookings.schedule.edit');
    Route::patch('/owners/bookings/{booking}/reagendar', [BookingController::class, 'updateSchedule'])
        ->name('bookings.schedule.update');

    // Caixa da arena atual.
    Route::get('/owners/caixa', [CashRegisterController::class, 'index'])
        ->name('caixa.index');
    // Páginas de cada seção (fixas ANTES do {caixa} para não conflitar).
    Route::get('/owners/caixa/reservas-a-receber', [CashRegisterController::class, 'receivables'])
        ->name('caixa.receivables');
    Route::get('/owners/caixa/taxas-a-receber', [CashRegisterController::class, 'fees'])
        ->name('caixa.fees');
    Route::get('/owners/caixa/pagamentos-a-lancar', [CashRegisterController::class, 'pendingPayments'])
        ->name('caixa.pending-payments');
    Route::get('/owners/caixa/lancamento/{entry}', [CashRegisterController::class, 'showEntry'])
        ->name('caixa.entry.show');
    Route::post('/owners/caixa/pagamentos/{payment}/lancar', [CashRegisterController::class, 'launchPayment'])
        ->name('caixa.launch-payment');
    Route::get('/owners/caixa/lancamentos', [CashRegisterController::class, 'entries'])
        ->name('caixa.entries');
    Route::get('/owners/caixa/fechados', [CashRegisterController::class, 'closed'])
        ->name('caixa.closed');
    Route::get('/owners/caixa/financeiro', [CashRegisterController::class, 'report'])
        ->name('caixa.report');
    Route::get('/owners/caixa/financeiro/lancamentos', [CashRegisterController::class, 'reportEntries'])
        ->name('caixa.report.entries');
    Route::get('/owners/caixa/balanco', [CashRegisterController::class, 'balance'])
        ->name('caixa.balance');
    Route::post('/owners/caixa/abrir', [CashRegisterController::class, 'open'])
        ->name('caixa.open');
    Route::post('/owners/caixa/lancamento', [CashRegisterController::class, 'entry'])
        ->name('caixa.entry');
    Route::post('/owners/caixa/reservas/{booking}/receber', [CashRegisterController::class, 'pay'])
        ->name('caixa.pay');
    Route::post('/owners/caixa/reservas/{booking}/receber-taxa', [CashRegisterController::class, 'payFee'])
        ->name('caixa.pay-fee');
    Route::post('/owners/caixa/fechar', [CashRegisterController::class, 'close'])
        ->name('caixa.close');
    Route::get('/owners/caixa/{caixa}', [CashRegisterController::class, 'show'])
        ->name('caixa.show');
});

// Área do cliente — navegar nas arenas.
Route::middleware(['auth'])->group(function () {
    Route::get('/client/arenas', [ClientArenaController::class, 'index'])
        ->name('client.arenas.index');

    // Favoritas: a listagem vem antes de /{arena} para "favoritas" não ser
    // interpretado como o parâmetro {arena} da rota de detalhes.
    Route::get('/client/arenas/favoritas', [ClientFavoriteController::class, 'index'])
        ->name('client.arenas.favoritas');

    Route::post('/client/arenas/{arena}/favoritar', [ClientFavoriteController::class, 'toggle'])
        ->name('client.arenas.favoritar');

    // Ver os detalhes de uma arena é PÚBLICO: o visitante olha a arena antes de
    // criar conta. Fica aqui (depois de "favoritas") para a ordem das rotas não
    // capturar "favoritas" como {arena}; o withoutMiddleware libera o visitante.
    Route::get('/client/arenas/{arena}', [ClientArenaController::class, 'show'])
        ->name('client.arenas.show')
        ->withoutMiddleware(['auth']);

    // Reservar uma quadra.
    Route::get('/client/arenas/{arena}/quadras/{court}/reservar', [ClientBookingController::class, 'create'])
        ->name('client.bookings.create');

    Route::post('/client/arenas/{arena}/quadras/{court}/reservar', [ClientBookingController::class, 'store'])
        ->name('client.bookings.store');

    Route::get('/client/reserva-confirmada', function () {
        return view('client.bookings.success');
    })->name('client.bookings.success');

    // Notificações do cliente.
    Route::get('/notificacoes', [ClientNotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('/notificacoes/ler-todas', [ClientNotificationController::class, 'readAll'])
        ->name('notifications.readAll');
    Route::get('/notificacoes/{notification}', [ClientNotificationController::class, 'show'])
        ->name('notifications.show');

    // Sugestões e reporte de bugs (qualquer usuário logado envia).
    Route::get('/sugestoes', [FeedbackController::class, 'create'])
        ->name('feedback.create');
    Route::post('/sugestoes', [FeedbackController::class, 'store'])
        ->name('feedback.store');

    // Detalhes completos de uma reserva (cliente dono, dono da arena ou funcionário).
    Route::get('/reservas/{booking}/detalhes', [BookingDetailController::class, 'show'])
        ->name('bookings.show');

    // Próximos agendamentos, histórico e cancelar.
    Route::get('/client/reservas', [ClientBookingController::class, 'index'])
        ->name('client.bookings.index');

    Route::get('/client/reservas/hoje', [ClientBookingController::class, 'today'])
        ->name('client.bookings.today');

    Route::get('/client/reservas/pendentes', [ClientBookingController::class, 'pending'])
        ->name('client.bookings.pending');

    Route::get('/client/pagamentos-pendentes', [ClientBookingController::class, 'unpaidPayments'])
        ->name('client.bookings.unpaid');

    Route::get('/client/reservas/{booking}/editar', [ClientBookingController::class, 'edit'])
        ->name('client.bookings.edit');

    Route::patch('/client/reservas/{booking}', [ClientBookingController::class, 'update'])
        ->name('client.bookings.update');

    Route::get('/client/reservas/historico', [ClientBookingController::class, 'history'])
        ->name('client.bookings.history');

    Route::patch('/client/reservas/{booking}/cancelar', [ClientBookingController::class, 'cancel'])
        ->name('client.bookings.cancel');

    // Pagamento (simulado) da reserva confirmada.
    Route::get('/client/reservas/{booking}/pagar', [ClientBookingController::class, 'pay'])
        ->name('client.bookings.pay');
    Route::post('/client/reservas/{booking}/pagar', [ClientBookingController::class, 'payConfirm'])
        ->name('client.bookings.pay.confirm');

    // Cancelamento COM taxa: paga a taxa online para cancelar.
    Route::get('/client/reservas/{booking}/cancelar-taxa', [ClientBookingController::class, 'cancelPay'])
        ->name('client.bookings.cancel-pay');
    Route::post('/client/reservas/{booking}/cancelar-taxa', [ClientBookingController::class, 'cancelPayConfirm'])
        ->name('client.bookings.cancel-pay.confirm');

    // Perfil do cliente.
    Route::get('/client/perfil', [ClientProfileController::class, 'edit'])
        ->name('client.profile.edit');

    Route::patch('/client/perfil', [ClientProfileController::class, 'update'])
        ->name('client.profile.update');

    Route::put('/client/perfil/senha', [ClientProfileController::class, 'updatePassword'])
        ->name('client.profile.password');

    Route::delete('/client/perfil', [ClientProfileController::class, 'destroy'])
        ->name('client.profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::patch('/arenas/{arena}/pagamentos', [ArenaController::class, 'updatePayments'])
        ->name('arenas.payments.update');
    Route::patch('/arenas/{arena}/toggle', [ArenaController::class, 'toggleActive'])
        ->name('arenas.toggle');
    Route::post('/arenas/{arena}/desativar/confirmar', [ArenaController::class, 'confirmDeactivate'])
        ->name('arenas.deactivate.confirm');
    Route::post('/arenas/{arena}/excluir/confirmar', [ArenaController::class, 'confirmDelete'])
        ->name('arenas.delete.confirm');
    Route::patch('/arenas/{arena}/nome', [ArenaController::class, 'updateName'])
        ->name('arenas.name.update');
    Route::patch('/arenas/{arena}/contato', [ArenaController::class, 'updateContact'])
        ->name('arenas.contact.update');
    Route::patch('/arenas/{arena}/taxa-cancelamento', [ArenaController::class, 'updateCancellationFee'])
        ->name('arenas.fee.update');
    Route::patch('/arenas/{arena}/horarios', [ArenaController::class, 'updateBusinessHours'])
        ->name('arenas.hours.update');
    Route::post('/arenas/{arena}/horarios/confirmar', [ArenaController::class, 'confirmBusinessHours'])
        ->name('arenas.hours.confirm');
    Route::resource('arenas', ArenaController::class);
    Route::patch('/quadras/{quadra}/toggle', [QuadraController::class, 'toggleActive'])
        ->name('quadras.toggle');
    Route::post('/quadras/{quadra}/desativar/confirmar', [QuadraController::class, 'confirmDeactivate'])
        ->name('quadras.deactivate.confirm');
    Route::resource('quadras', QuadraController::class);
    Route::resource('employees', EmployeeController::class);
    Route::patch('/employees/{employee}/toggle', [EmployeeController::class, 'toggleActive'])
        ->name('employees.toggle');
});
