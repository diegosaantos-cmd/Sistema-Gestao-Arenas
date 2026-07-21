<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\ArenaController;
use App\Http\Controllers\Concerns\RenovaSessaoAposTrocaDeSenha;
use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use RenovaSessaoAposTrocaDeSenha;

    /**
     * Tela "Minha Conta" do dono: dados pessoais + empresa + senha.
     */
    public function edit()
    {
        $owner = $this->owner();
        $user = auth()->user();

        return view('owners.profile.edit', compact('owner', 'user'));
    }

    /**
     * Atualiza os dados pessoais (nome, e-mail, telefone).
     */
    public function updatePersonal(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // trocarEmail: se o e-mail mudou e a verificação estiver ligada, a conta
        // volta a "não verificada" e o link vai para o NOVO endereço.
        $reverificar = $user->trocarEmail($validated['email']);

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        return back()->with('status', $reverificar
            ? 'Dados atualizados. Enviamos um link para o novo e-mail — confirme-o para continuar usando o sistema.'
            : 'Dados pessoais atualizados.');
    }

    /**
     * Atualiza os dados da empresa (nome da empresa e CPF/CNPJ), com a mesma
     * regra de unicidade do cadastro (ignorando espaços/maiúsculas).
     */
    public function updateCompany(Request $request)
    {
        $owner = $this->owner();

        $request->merge([
            'company_name' => ArenaController::normalizarTexto($request->input('company_name')),
            'tax_id' => preg_replace('/\D/', '', (string) $request->input('tax_id')),
        ]);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150', function ($attribute, $value, $fail) use ($owner) {
                $chave = ArenaController::chaveComparacao($value);
                if (Owner::whereRaw("REPLACE(LOWER(company_name), ' ', '') = ?", [$chave])
                        ->where('id', '!=', $owner->id)
                        ->exists()) {
                    $fail('Já existe uma empresa cadastrada com esse nome.');
                }
            }],
            'tax_id' => [
                'required', 'regex:/^(\d{11}|\d{14})$/',
                // whereNull('deleted_at'): documento de empresa EXCLUÍDA fica livre.
                Rule::unique('owners', 'tax_id')->ignore($owner->id)->whereNull('deleted_at'),
            ],
        ], [
            'tax_id.regex' => 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.',
            'tax_id.unique' => 'Este CPF/CNPJ já está em uso.',
        ]);

        $owner->update([
            'company_name' => $validated['company_name'],
            'tax_id' => $validated['tax_id'],
        ]);

        return back()->with('status', 'Dados da empresa atualizados.');
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

        $user->update(['password_hash' => $request->password]);
        $this->renovarHashDaSenhaNaSessao($request);

        return back()->with('status', 'Senha alterada com sucesso.');
    }

    /**
     * Encerra a conta do dono e a empresa dele.
     *
     * REGRA: só é permitido com NENHUMA arena ativa (todas desativadas ou
     * excluídas). A arena ativa é o negócio funcionando — tem horário de
     * funcionamento, formas de pagamento, quadras e agenda dependendo dela.
     * O dono precisa encerrar o próprio fluxo no site antes de sair.
     *
     * O que acontece:
     *   - os funcionários das arenas dele são encerrados (arena desativada não
     *     encerra funcionário, então sem isto eles ficariam logando num negócio
     *     que não existe mais — ver ArenaController::encerrarFuncionariosDaArena);
     *   - a conta é encerrada por User::encerrarConta(), que LIBERA o e-mail;
     *   - a empresa vira soft delete, o que LIBERA o nome e o CPF/CNPJ (colunas
     *     geradas empresa_ativa/documento_ativo ficam nulas).
     *
     * O REGISTRO fica: arenas, quadras, reservas, pagamentos e caixa continuam,
     * assim como o nome da empresa e o CPF/CNPJ. Os DADOS PESSOAIS somem — o
     * histórico passa a identificar o dono pela função ("Proprietário removido
     * #id"), e o telefone/e-mail de contato das arenas é apagado.
     *
     * Se ele (ou outra pessoa) quiser voltar, é só cadastrar de novo — será uma
     * empresa NOVA, do zero.
     */
    public function destroy(Request $request)
    {
        $owner = $this->owner();
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

        $arenasAtivas = $owner->arenas()->where('active', true)->pluck('name');

        if ($arenasAtivas->isNotEmpty()) {
            return back()->withErrors([
                'delete_account' => 'Você ainda tem arena ativa ('.$arenasAtivas->implode(', ').'). '
                    .'Desative ou exclua todas as suas arenas antes de encerrar a conta.',
            ], 'deleteAccount');
        }

        $emailLiberado = $user->email;

        DB::transaction(function () use ($owner, $user) {
            // Arenas desativadas ainda têm funcionários vinculados: encerra
            // todos, senão continuariam logando depois que o dono sair.
            foreach ($owner->arenas()->withTrashed()->get() as $arena) {
                ArenaController::encerrarFuncionariosDaArena($arena);
                // Telefone/e-mail de contato somem (costumam ser os pessoais do
                // dono). Nome, endereço, quadras e histórico ficam.
                $arena->anonimizarContato();
            }

            // Os dados PESSOAIS do dono somem (o nome vira "Proprietário
            // removido #id"). O registro do negócio é da EMPRESA — company_name
            // e tax_id continuam guardados.
            $user->encerrarConta();
            $owner->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status',
            'Conta encerrada. O histórico das suas arenas foi preservado. Se quiser voltar, '
            .'pode se cadastrar novamente com o e-mail '.$emailLiberado.' — será uma empresa nova.');
    }

    /**
     * Owner do usuário logado (ou 403 se não for dono).
     */
    private function owner(): Owner
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários têm acesso a esta área.');
        }

        return $owner;
    }
}