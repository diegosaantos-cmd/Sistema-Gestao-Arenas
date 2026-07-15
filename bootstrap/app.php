<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'pode.gerir' => \App\Http\Middleware\PodeGerirArena::class,
    ]);

    // AuthenticateSession em TODO o grupo web: ao trocar a senha de uma conta,
    // as demais sessões abertas dela caem na próxima requisição (importante após
    // um "esqueci a senha" — derruba a sessão de um invasor). Ignora visitantes.
    // As telas de troca da PRÓPRIA senha re-semeiam o hash na sessão para o
    // usuário não se autodeslogar (ver trait RenovaSessaoAposTrocaDeSenha).
    $middleware->web(append: [
        \Laravel\Jetstream\Http\Middleware\AuthenticateSession::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
