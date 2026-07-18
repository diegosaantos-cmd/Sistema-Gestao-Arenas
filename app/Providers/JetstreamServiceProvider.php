<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    /**
     * A exclusão de conta NÃO é registrada aqui de propósito. Ela é feita pelas
     * telas do próprio sistema, que passam por User::encerrarConta() — o único
     * lugar que anonimiza, libera o e-mail e derruba a sessão. O DeleteUser do
     * Jetstream foi removido para não existir um segundo caminho de exclusão
     * (inalcançável, mas que apagaria a conta sem nenhuma dessas regras).
     */
    public function boot(): void
    {
        $this->configurePermissions();
    }

    /**
     * Configure the permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
    }
}
