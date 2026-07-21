<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Concerns\RenovaSessaoAposTrocaDeSenha;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * "Minha Conta" do funcionário (hoje usada pelo gerente). Ao contrário do dono,
 * não há dados de empresa: só os dados pessoais de acesso e a senha. A arena e o
 * cargo aparecem apenas para consulta — quem os define é o dono.
 */
class ProfileController extends Controller
{
    use RenovaSessaoAposTrocaDeSenha;

    public function edit()
    {
        $employee = $this->employee();
        $user = auth()->user();

        return view('employees.profile.edit', compact('employee', 'user'));
    }

    /**
     * Atualiza os dados pessoais (nome, e-mail, telefone).
     */
    public function updatePersonal(Request $request)
    {
        // O atendente só consulta os próprios dados; quem edita é o gerente/dono.
        abort_if(\App\Support\ArenaAtual::ehAtendente(), 403);

        $this->employee();
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'email.unique' => 'Já existe um usuário com esse e-mail.',
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
     * Altera a senha (confere a senha atual).
     */
    public function updatePassword(Request $request)
    {
        $this->employee();
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
     * Vínculo de funcionário (ativo) do usuário logado, ou 403 se não for.
     */
    private function employee(): Employee
    {
        $employee = Employee::with('arena')
            ->where('user_id', auth()->id())
            ->where('active', true)
            ->first();

        if (! $employee) {
            abort(403, 'Apenas funcionários têm acesso a esta área.');
        }

        return $employee;
    }
}
