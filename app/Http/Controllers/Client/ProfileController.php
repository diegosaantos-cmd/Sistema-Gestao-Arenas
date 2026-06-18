<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Tela de perfil do cliente (dados pessoais + troca de senha).
     */
    public function edit()
    {
        $user = auth()->user();

        return view('client.profile.edit', compact('user'));
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
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => mb_strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
        ]);

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

        return back()->with('status', 'Senha alterada com sucesso.');
    }
}
