<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- ?v=timestamp: o navegador busca a versão nova sempre que o CSS muda,
         sem depender de Ctrl+F5. --}}
    <link rel="stylesheet" href="/css/style.css?v={{ @filemtime(public_path('css/style.css')) }}">
    <script src="/js/script.js"></script>

</head>

<body>
    <header class="sticky-top">
        <nav class="navbar navbar-expand-lg custom-navbar">
            <div class="container-fluid px-4">

                <!-- Logo -->
                <a class="navbar-brand logo" href="/">
                    ArenaPlay
                </a>

                @guest
                    {{-- "Entrar" SEMPRE visível no celular: fica fora do menu que colapsa,
                         para o visitante ver na hora como acessar o site. Some no desktop
                         (d-lg-none), onde o menu completo já mostra "ENTRAR". --}}
                    <a href="/login" class="btn btn-outline-light btn-sm d-lg-none ms-auto me-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                    </a>
                @endguest

                <!-- Botão Mobile -->
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav">

                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Menu -->
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">

                    <ul class="navbar-nav align-items-center gap-4">

                        {{-- Funcionário (gerente/atendente) não vê "Início": o painel
                             dele é "Minha Área"; a tela pública inicial não lhe serve. --}}
                        @unless (auth()->check() && auth()->user()->type === 'employee')
                            <li class="nav-item">
                                <a class="nav-link active" href="/">
                                    <i class="bi bi-house-fill"></i>
                                    INÍCIO
                                </a>
                            </li>
                        @endunless

                        @guest
                            {{-- No celular o "Entrar" já fica no botão fixo fora do menu
                                 (sempre visível); aqui dentro do menu ele só aparece no
                                 desktop (d-none d-lg-block) — evita dois "Entrar". --}}
                            <li class="nav-item d-none d-lg-block">
                                <a class="nav-link" href="/login">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    ENTRAR
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register.arena.owners') }}">
                                    CADASTRAR UMA ARENA
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="btn btn-register" href="/register">
                                    CRIAR CONTA
                                </a>
                            </li>
                        @endguest

                        @auth
                            @php
                                $tipo = auth()->user()->type;
                                $route = '/dashboard';

                                if ($tipo === 'admin') {
                                    $route = '/admin';
                                } elseif ($tipo === 'owner') {
                                    $route = '/owners/dashboard';
                                } elseif ($tipo === 'employee') {
                                    // Gerente cai no painel do dono (reaproveitado);
                                    // atendente, no painel próprio.
                                    $route = \App\Support\ArenaAtual::ehGerente()
                                        ? '/owners/dashboard'
                                        : '/employees/dashboard';
                                }
                            @endphp
                            <li class="nav-item">
                                <a class="nav-link" href="{{ $route }}">
                                    <i class="bi bi-grid-fill"></i>
                                    MINHA ÁREA
                                </a>
                            </li>

                            @if (auth()->user()->type === 'owner')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('owner.profile.edit') }}">
                                        <i class="bi bi-person-gear"></i>
                                        MINHA CONTA
                                    </a>
                                </li>
                            @elseif (auth()->user()->type === 'employee')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('employee.profile.edit') }}">
                                        <i class="bi bi-person-gear"></i>
                                        MINHA CONTA
                                    </a>
                                </li>
                            @elseif (auth()->user()->type === 'client')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('client.profile.edit') }}">
                                        <i class="bi bi-person-gear"></i>
                                        MINHA CONTA
                                    </a>
                                </li>
                            @endif

                            @if (auth()->user()->type !== 'admin')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('feedback.create') }}" title="Sugestões e bugs">
                                        <i class="bi bi-chat-left-dots"></i>
                                        SUGESTÕES
                                    </a>
                                </li>
                            @endif

                            <li class="nav-item">
                                <a class="nav-link position-relative" href="{{ route('notifications.index') }}"
                                   title="Notificações">
                                    <i class="bi bi-bell-fill fs-5"></i>
                                    @if (($notifUnreadCount ?? 0) > 0)
                                        <span class="position-absolute badge rounded-pill bg-danger"
                                              style="top: 0; left: 100%; transform: translate(-80%, 0);
                                                     font-size: .55rem; padding: .2em .45em; line-height: 1;">
                                            {{ $notifUnreadCount > 99 ? '99+' : $notifUnreadCount }}
                                            <span class="visually-hidden">mensagens não lidas</span>
                                        </span>
                                    @endif
                                </a>
                            </li>


                            <li class="nav-item">
                                <form action="/logout" method="POST">
                                    @csrf
                                    <button class="btn btn-danger">
                                        SAIR
                                    </button>
                                </form>
                            </li>
                        @endauth

                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main>
        <div class="container-fluid">
            <div class="row">
                @if(session('msg'))
                    <div class="col-12">
                        <div class="alert alert-success alert-dismissible fade show mt-3 shadow-sm" role="alert">
                            ✅ {{ session('msg') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    </div>
                @endif
                @if(session('erro'))
                    <div class="col-12">
                        <div class="alert alert-danger alert-dismissible fade show mt-3 shadow-sm" role="alert">
                            ❌ {{ session('erro') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    </div>
                @endif
                @if(session('aviso'))
                    <div class="col-12">
                        <div class="alert alert-warning alert-dismissible fade show mt-3 shadow-sm" role="alert">
                            ⚠️ {{ session('aviso') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="/js/favorites.js" defer></script>
    <script>
        // Navegação "lateral" entre a reserva e o lançamento do caixa (botões
        // "Ver lançamento completo" / "Ver reserva completa"). Se o destino é
        // exatamente a página de onde eu vim, volto pelo histórico (history.back)
        // em vez de empilhar uma nova entrada. Assim, alternar entre as duas telas
        // não acumula histórico e o "Voltar" chega à origem em poucos passos —
        // sem o "loop" de ter que desfazer cada alternância. Fallback: navegação
        // normal (empilha) se o referrer não estiver disponível.
        function arenaCrossNav(e, url) {
            try {
                var ref = document.referrer ? new URL(document.referrer).pathname : '';
                var alvo = new URL(url, window.location.origin).pathname;
                if (ref && ref === alvo && window.history.length > 1) {
                    e.preventDefault();
                    window.history.back();
                    return false;
                }
            } catch (err) { /* referrer indisponível: segue navegação normal */ }
            return true;
        }
    </script>
</body>

</html>
