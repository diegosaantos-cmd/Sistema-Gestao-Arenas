<?php

namespace App\Actions\Fortify;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Valida e cria um cliente recém-cadastrado.
     *
     * Cria também o registro em `clients`. Antes ele só nascia na primeira
     * reserva (Client::firstOrCreate), e por isso a data de nascimento nunca
     * era coletada no cadastro.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input['phone'] = trim((string) ($input['phone'] ?? ''));

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            'name.required' => 'Informe seu nome completo.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'phone.required' => 'Informe seu telefone.',
            'date_of_birth.date' => 'Informe uma data de nascimento válida.',
            'date_of_birth.before_or_equal' => 'A data de nascimento não pode ser no futuro.',
            'password.required' => 'Crie uma senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'terms.accepted' => 'É preciso aceitar os termos de uso.',
            'terms.required' => 'É preciso aceitar os termos de uso.',
        ])->validate();

        // Ou cria usuário E cliente, ou não cria nada.
        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => mb_strtolower(trim($input['email'])),
                'phone' => $input['phone'],
                'password_hash' => Hash::make($input['password']),
                'terms_accepted_at' => now(),
                'type' => 'client',
            ]);

            Client::create([
                'user_id' => $user->id,
                'date_of_birth' => $input['date_of_birth'] ?? null,
            ]);

            return $user;
        });
    }
}
