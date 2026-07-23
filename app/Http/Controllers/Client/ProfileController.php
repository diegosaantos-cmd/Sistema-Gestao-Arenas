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

        // Dívida com a arena: a tela avisa disso ANTES, em vez de deixar o
        // cliente digitar a senha para só então descobrir que está travado.
        $reservasEmAberto = $client?->reservasEmAberto()->count() ?? 0;
        $valorEmAberto = $reservasEmAberto > 0 ? $client->valorEmAberto() : 0.0;

        return view('client.profile.edit', compact(
            'user', 'client', 'reservasEmAberto', 'valorEmAberto'
        ));
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

        // trocarEmail: se o e-mail mudou e a verificação estiver ligada, a conta
        // volta a "não verificada" e o link vai para o NOVO endereço.
        $reverificar = $user->trocarEmail($validated['email']);

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        Client::updateOrCreate(
            ['user_id' => $user->id],
            ['date_of_birth' => $validated['date_of_birth'] ?? null]
        );

        return back()->with('status', $reverificar
            ? 'Dados atualizados. Enviamos um link para o novo e-mail — confirme-o para continuar usando o sistema.'
            : 'Dados atualizados com sucesso.');
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
     * Estratégia de ANONIMIZAÇÃO — o REGISTRO fica, o DADO PESSOAL some:
     *
     * 1. Os dados pessoais nas reservas são anonimizados: guest_name/phone/email
     *    e notes viram marcadores. O VÍNCULO com o cliente (client_id) é
     *    PRESERVADO — a reserva passa a mostrar "Cliente removido", que vem do
     *    nome (já anonimizado) do usuário, não de um campo solto.
     * 2. A data de nascimento do cliente é apagada (coluna de data, sem marcador).
     * 3. A conta é encerrada (User::encerrarConta): nome vira o rótulo genérico
     *    ("Cliente removido"), telefone vira o marcador "Removido", o e-mail é
     *    liberado (placeholder) para um novo cadastro, active=false e a sessão é
     *    derrubada.
     * 4. O vínculo de cliente vira soft delete.
     *
     * Reservas, pagamentos e caixa NÃO são apagados — é o registro contábil da
     * arena, e ele não identifica mais ninguém. Um novo cadastro com o e-mail
     * liberado é uma conta NOVA, do zero (o histórico antigo fica com a arena).
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

        // Horários já usados e não pagos: é dívida com a arena, então a conta
        // não sai sem quitar. O bloqueio acima não cobre isto — ele procura um
        // pagamento com status pendente, e quem nunca pagou não tem pagamento
        // nenhum registrado.
        $emAberto = $client?->reservasEmAberto()->count() ?? 0;

        if ($emAberto > 0) {
            $valor = number_format($client->valorEmAberto(), 2, ',', '.');

            // Diz quanto e quantos: sem isso o cliente sabe que está travado,
            // mas não o que fazer para destravar.
            $impedimentos[] = $emAberto === 1
                ? "Você tem 1 horário realizado e não pago, no valor de R$ {$valor}. "
                    .'Quite o pagamento antes de excluir a conta.'
                : "Você tem {$emAberto} horários realizados e não pagos, num total de R$ {$valor}. "
                    .'Quite os pagamentos antes de excluir a conta.';
        }

        if ($impedimentos) {
            return back()->withErrors([
                'delete_account' => implode(' ', $impedimentos),
            ], 'deleteAccount');
        }

        $emailLiberado = $user->email;

        DB::transaction(function () use ($user, $client) {
            // 1. Desliga as reservas do cliente e APAGA os dados pessoais delas.
            //    O registro (data, quadra, valor, pagamento, caixa) fica; a
            //    identidade não. A reserva passa a mostrar "Cliente excluído".
            $client?->desligarReservasAnonimizando();
            $client?->anonimizarDadosPessoais();

            // 2. Encerra a conta: anonimiza, libera o e-mail, derruba a sessão e
            //    desativa o acesso. O NOME é apagado aqui (ao contrário de
            //    funcionário/admin) porque a reserva já tem o snapshot do passo 1.
            $user->encerrarConta();

            // 3. Encerra o vínculo de cliente.
            $client?->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Sua conta foi excluída. Se um dia quiser voltar, pode se cadastrar novamente com o e-mail '.$emailLiberado.' — será uma conta nova.');
    }
}
