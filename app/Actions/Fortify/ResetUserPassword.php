<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        // Ao trocar `password_hash`, o middleware AuthenticateSession (ativo via
        // jetstream.auth_session) invalida as OUTRAS sessões abertas dessa conta
        // na próxima requisição delas — inclusive a de um dispositivo comprometido.
        // (Não há coluna remember_token nesta base; nada a rotacionar aqui.)
        $user->forceFill([
            'password_hash' => Hash::make($input['password']),
        ])->save();
    }
}
