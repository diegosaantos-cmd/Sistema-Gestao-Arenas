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

        // Quem registrou (se foi um funcionário).
        $funcionario = $booking->employee_id
            ? Employee::with('user')->find($booking->employee_id)
            : null;

        // Quem cancelou, com o tipo (Cliente / Dono / Gerente / Atendente).
        $canceladoPor = null;
        if ($booking->cancelled_by) {
            $u = User::find($booking->cancelled_by);
            if ($u) {
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

                $canceladoPor = ($tipo ? $tipo . ': ' : '') . $u->name;
            }
        }

        return view('bookings.details', compact('booking', 'funcionario', 'canceladoPor'));
    }
}
