<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\ArenaController;
use App\Http\Controllers\Concerns\RenovaSessaoAposTrocaDeSenha;
use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;
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

        $user->update([
            'name' => $validated['name'],
            'email' => mb_strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
        ]);

        return back()->with('status', 'Dados pessoais atualizados.');
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
                Rule::unique('owners', 'tax_id')->ignore($owner->id),
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