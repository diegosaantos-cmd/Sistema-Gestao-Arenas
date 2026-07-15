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
     * Exclui permanentemente a conta do cliente quando não há pendências.
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

        DB::transaction(function () use ($user, $client, $bookingIds) {
            if ($bookingIds->isNotEmpty()) {
                DB::table('payments')->whereIn('booking_id', $bookingIds)->delete();
                Booking::whereIn('id', $bookingIds)->delete();
            }

            $client?->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            if (Schema::hasTable('personal_access_tokens')) {
                $user->tokens()->delete();
            }
            $user->deleteProfilePhoto();
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Sua conta foi excluída permanentemente.');
    }
}
