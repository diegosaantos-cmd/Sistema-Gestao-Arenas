<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\RenovaSessaoAposTrocaDeSenha;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use RenovaSessaoAposTrocaDeSenha;

    /**
     * Tela de perfil do cliente (dados pessoais + troca de senha).
     */
    public function edit()
    {
        $user = auth()->user();
        $client = Client::where('user_id', $user->id)->first();

        return view('client.profile.edit', compact('user', 'client'));
    }

    /**
     * Atualiza nome, e-mail e telefone.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => mb_strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
        ]);

        Client::updateOrCreate(
            ['user_id' => $user->id],
            ['date_of_birth' => $validated['date_of_birth'] ?? null]
        );

        return back()->with('status', 'Dados atualizados com sucesso.');
    }

    /**
     * Altera a senha (confere a senha atual).
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'password.confirmed' => 'A confirmação da nova senha não confere.',
            'password.min' => 'A nova senha deve ter ao menos 8 caracteres.',
        ]);

        if (! Hash::check($request->current_password, $user->password_hash)) {
            return back()->withErrors(['current_password' => 'Senha atual incorreta.']);
        }

        // O cast 'hashed' do model faz o hash automaticamente.
        $user->update(['password_hash' => $request->password]);
        $this->renovarHashDaSenhaNaSessao($request);

        return back()->with('status', 'Senha alterada com sucesso.');
    }

    /**
     * Encerra a conta do cliente quando não há pendências.
     *
     * Estratégia de ANONIMIZAÇÃO (libera o e-mail para reuso e mantém o
     * histórico da arena correto):
     *
     * 1. Cada reserva do cliente guarda um SNAPSHOT do nome/telefone/e-mail
     *    (colunas guest_*, as mesmas da reserva presencial) e é desligada do
     *    cliente (client_id = null). Assim a reserva vira auto-suficiente: o
     *    dono continua vendo quem reservou, sem depender do cadastro.
     * 2. O e-mail do usuário é trocado por um placeholder, LIBERANDO o e-mail
     *    original para um novo cadastro. Nome/telefone são apagados.
     * 3. A conta é desativada (soft delete de user + client) e a sessão encerrada.
     *
     * Reservas e pagamentos NÃO são apagados — são o histórico e o caixa da arena.
     * Um novo cadastro com o e-mail liberado é uma conta NOVA, do zero (o histórico
     * antigo fica com a arena, não volta para o cliente).
     */
    public function destroy(Request $request)
    {
        $user = auth()->user();

        $request->validateWithBag('deleteAccount', [
            'delete_password' => ['required'],
        ], [
            'delete_password.required' => 'Informe sua senha para excluir a conta.',
        ]);

        if (! Hash::check($request->delete_password, $user->password_hash)) {
            return back()->withErrors([
                'delete_password' => 'A senha informada está incorreta.',
            ], 'deleteAccount');
        }

        if ($user->type !== 'client') {
            return back()->withErrors([
                'delete_account' => 'Esta exclusão está disponível somente para contas de cliente.',
            ], 'deleteAccount');
        }

        $client = Client::where('user_id', $user->id)->first();
        $bookingIds = $client
            ? Booking::where('client_id', $client->id)->pluck('id')
            : collect();

        $temAgendamentoAtivo = $client && Booking::where('client_id', $client->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        $temPagamentoPendente = $bookingIds->isNotEmpty()
            && DB::table('payments')
                ->whereIn('booking_id', $bookingIds)
                ->where('status', 'pending')
                ->exists();

        $impedimentos = [];

        if ($temAgendamentoAtivo) {
            $impedimentos[] = 'Você não pode excluir a conta enquanto possuir horários agendados.';
        }

        if ($temPagamentoPendente) {
            $impedimentos[] = 'Você não pode excluir a conta enquanto possuir pagamentos pendentes.';
        }

        if ($impedimentos) {
            return back()->withErrors([
                'delete_account' => implode(' ', $impedimentos),
            ], 'deleteAccount');
        }

        $emailLiberado = $user->email;

        DB::transaction(function () use ($user, $client) {
            // 1. Congela o nome/contato nas reservas e as desliga do cliente.
            //    A reserva passa a se sustentar sozinha (como uma presencial).
            if ($client) {
                Booking::where('client_id', $client->id)->update([
                    'guest_name'  => $user->name ?: 'Cliente removido',
                    'guest_phone' => $user->phone,
                    'guest_email' => $user->email,
                    'client_id'   => null,
                ]);
            }

            // 2. Anonimiza o usuário e LIBERA o e-mail original para novo cadastro.
            $user->deleteProfilePhoto();
            $user->forceFill([
                'name'  => 'Conta removida',
                'email' => 'removido_'.$user->id.'_'.Str::lower(Str::random(8)).'@conta.invalid',
                'phone' => null,
            ])->save();

            // 3. Desativa a conta (soft delete) e encerra sessão/tokens.
            $client?->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            if (Schema::hasTable('personal_access_tokens')) {
                $user->tokens()->delete();
            }
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Sua conta foi excluída. Se um dia quiser voltar, pode se cadastrar novamente com o e-mail '.$emailLiberado.' — será uma conta nova.');
    }
}
