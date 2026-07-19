<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\User;

class BookingDetailController extends Controller
{
    /**
     * Detalhes completos de uma reserva. Acessível pelo cliente dono dela,
     * pelo dono da arena ou por um funcionário da arena.
     */
    public function show(Booking $booking)
    {
        // Arena carregada mesmo se excluída (soft delete), para o histórico.
        $booking->load([
            'court.arena' => fn ($q) => $q->withTrashed()->with('owner'),
            'client.user',
            'payments.paymentMethod',
        ]);

        $userId = auth()->id();

        $ehCliente = $booking->client && $booking->client->user_id === $userId;

        $arena = $booking->court?->arena;
        $ehDono = $arena && $arena->owner && $arena->owner->user_id === $userId;
        $ehFuncionario = $arena && Employee::where('arena_id', $arena->id)
            ->where('user_id', $userId)
            ->exists();

        if (! $ehCliente && ! $ehDono && ! $ehFuncionario) {
            abort(403);
        }

        // Só o staff (dono/gerente/atendente) pode abrir o lançamento no caixa; o
        // cliente não tem acesso ao caixa, então não vê o botão "Ver lançamento".
        $podeVerCaixa = $ehDono || $ehFuncionario;

        // Quem registrou a reserva (dono, gerente, atendente ou admin). Vem do
        // created_by — nas reservas feitas pelo próprio cliente no site é nulo.
        $registradaPor = $this->usuarioDaAcao($booking->created_by);

        // Quem cancelou, no mesmo formato.
        $canceladoPor = $this->usuarioDaAcao($booking->cancelled_by);

        // Número da reserva no contexto de quem está vendo: o cliente vê a
        // sequência dele; dono/funcionário veem a sequência da arena.
        $numeroReserva = $ehCliente ? $booking->numeroDoCliente() : $booking->numeroNaArena();

        return view('bookings.details', compact('booking', 'registradaPor', 'canceladoPor', 'numeroReserva', 'podeVerCaixa'));
    }

    /**
     * "Tipo: Nome" de um usuário pelo id (Cliente / Dono / Gerente / Atendente /
     * Administrador). Para funcionário, distingue gerente de atendente pelo
     * nível de acesso. Nulo quando não há usuário.
     */
    /**
     * O usuário que fez a ação (registrou ou cancelou a reserva).
     *
     * Devolve o MODEL, não um texto pronto: quem formata é o componente
     * <x-nome-autor>, usado por todas as telas que mostram autor. Assim o
     * histórico fica consistente em todo o sistema.
     *
     * withTrashed: quem teve a conta encerrada precisa continuar identificado.
     * Sem isso, `User::find()` devolvia nulo e a tela mostrava só um traço — o
     * dono não sabia se ninguém havia cancelado ou se a informação se perdeu.
     * O nome já vem anonimizado ("Gerente removido"), então não expõe a pessoa.
     */
    private function usuarioDaAcao(?int $userId): ?User
    {
        return $userId ? User::withTrashed()->find($userId) : null;
    }
}
