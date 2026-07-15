<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait RenovaSessaoAposTrocaDeSenha
{
    /**
     * Re-semeia o hash da senha guardado na sessão depois que o usuário troca a
     * PRÓPRIA senha. Sem isto, o middleware AuthenticateSession (global, no grupo
     * web) deslogaria a pessoa na próxima página — ele compara o hash guardado na
     * sessão com o atual e, ao ver que "mudou", derruba a sessão. Aqui atualizamos
     * o hash guardado para o novo, então só as OUTRAS sessões da conta caem.
     *
     * Espelha o storePasswordHashInSession do próprio middleware.
     */
    protected function renovarHashDaSenhaNaSessao(Request $request): void
    {
        $user = $request->user();

        if (! $user || ! $request->hasSession()) {
            return;
        }

        $hash = $user->getAuthPassword();

        try {
            $hash = Auth::guard()->hashPasswordForCookie($hash);
        } catch (\BadMethodCallException) {
            // Guard sem hashPasswordForCookie: guarda o hash cru (igual ao middleware).
        }

        $request->session()->put('password_hash_' . Auth::getDefaultDriver(), $hash);
    }
}
