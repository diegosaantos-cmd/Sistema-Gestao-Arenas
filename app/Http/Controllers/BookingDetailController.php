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
        $registradaPor = $this->descreverUsuario($booking->created_by);

        // Quem cancelou, no mesmo formato.
        $canceladoPor = $this->descreverUsuario($booking->cancelled_by);

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
    private function descreverUsuario(?int $userId): ?string
    {
        if (! $userId) {
            return null;
        }

        $u = User::find($userId);
        if (! $u) {
            return null;
        }

        $tipo = match ($u->type) {
            'client' => 'Cliente',
            'owner' => 'Dono',
            'admin' => 'Administrador',
            default => null,
        };

        if ($u->type === 'employee') {
            $emp = Employee::where('user_id', $u->id)->first();
            $tipo = ($emp && $emp->access_level === 'managerial') ? 'Gerente' : 'Atendente';
        }

        return ($tipo ? $tipo . ': ' : '') . $u->name;
    }
}
