<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CashRegister;
use App\Models\CashRegisterEntry;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Registra o pagamento de uma reserva. Se a arena tiver um caixa ABERTO,
     * lança a entrada no caixa na hora e vincula ao pagamento. Se estiver
     * fechado, o pagamento fica "a lançar" (cash_register_entry_id = null) até
     * o dono adicionar quando abrir o caixa. Retorna o Payment criado.
     */
    public static function registrar(Booking $booking, PaymentMethod $metodo, float $valor, string $origin, ?int $createdBy = null): Payment
    {
        $arena = $booking->court?->arena;

        $caixa = $arena
            ? CashRegister::where('arena_id', $arena->id)->where('status', 'open')->first()
            : null;

        return DB::transaction(function () use ($booking, $metodo, $valor, $origin, $caixa, $createdBy) {
            $entryId = null;

            if ($caixa) {
                $entry = CashRegisterEntry::create([
                    'cash_register_id' => $caixa->id,
                    'booking_id' => $booking->id,
                    'type' => 'income',
                    'amount' => $valor,
                    'description' => 'Pagamento reserva #' . $booking->id . ' — ' . $metodo->label,
                    'created_by' => $createdBy,
                ]);
                $entryId = $entry->id;
            }

            return Payment::create([
                'booking_id' => $booking->id,
                'payment_method_id' => $metodo->id,
                'amount' => $valor,
                'status' => 'paid',
                'origin' => $origin,
                'cash_register_entry_id' => $entryId,
                'paid_at' => now(),
            ]);
        });
    }

    /**
     * Lança no caixa (aberto) um pagamento que ficou pendente (feito com o
     * caixa fechado). Cria a entrada e vincula ao pagamento.
     */
    public static function lancarNoCaixa(Payment $pagamento, CashRegister $caixa, ?int $createdBy = null): void
    {
        DB::transaction(function () use ($pagamento, $caixa, $createdBy) {
            $entry = CashRegisterEntry::create([
                'cash_register_id' => $caixa->id,
                'booking_id' => $pagamento->booking_id,
                'type' => 'income',
                'amount' => $pagamento->amount,
                'description' => 'Pagamento reserva #' . $pagamento->booking_id
                    . ' — ' . ($pagamento->paymentMethod->label ?? 'Pagamento'),
                'created_by' => $createdBy,
            ]);

            $pagamento->update(['cash_register_entry_id' => $entry->id]);
        });
    }
}
