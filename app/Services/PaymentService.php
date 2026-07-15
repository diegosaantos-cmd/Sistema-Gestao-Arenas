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
    public static function registrar(Booking $booking, PaymentMethod $metodo, float $valor, string $origin, ?int $createdBy = null, ?string $descricao = null): Payment
    {
        $arena = $booking->court?->arena;

        $caixa = $arena
            ? CashRegister::where('arena_id', $arena->id)->where('status', 'open')->first()
            : null;

        $descricao = $descricao ?? 'Pagamento reserva #' . $booking->numeroNaArena() . ' — ' . $metodo->label;

        return DB::transaction(function () use ($booking, $metodo, $valor, $origin, $caixa, $createdBy, $descricao) {
            $entryId = null;

            if ($caixa) {
                $entry = CashRegisterEntry::create([
                    'cash_register_id' => $caixa->id,
                    'booking_id' => $booking->id,
                    'type' => 'income',
                    'amount' => $valor,
                    'description' => $descricao,
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
     * ESTORNA/REEMBOLSA o pagamento de uma reserva JÁ PAGA que está sendo
     * cancelada. Devolve (valor pago − taxa), ou tudo se não houver taxa.
     *
     * Espelha o registrar(): se o caixa estiver ABERTO, lança a SAÍDA (despesa)
     * na hora e vincula; se fechado, o reembolso fica pendente
     * (refund_cash_register_entry_id = null) e é lançado quando o caixa abrir.
     * A taxa (se houver) fica retida — já entrou como receita no pagamento.
     *
     * Retorna o Payment estornado, ou null se a reserva não estava paga.
     */
    public static function reembolsar(Booking $booking, float $taxa = 0, ?int $createdBy = null): ?Payment
    {
        $booking->loadMissing('court.arena', 'payments');

        $pagamento = $booking->payments
            ->where('status', 'paid')
            ->whereNull('refunded_at')
            ->sortByDesc('id')
            ->first();

        if (! $pagamento) {
            return null; // não estava paga: nada a reembolsar
        }

        $taxa = max(0.0, round($taxa, 2));
        $reembolso = max(0.0, round((float) $pagamento->amount - $taxa, 2));

        $arena = $booking->court?->arena;
        $caixa = $arena
            ? CashRegister::where('arena_id', $arena->id)->where('status', 'open')->first()
            : null;

        DB::transaction(function () use ($pagamento, $booking, $reembolso, $caixa, $createdBy) {
            $entryId = null;

            if ($caixa && $reembolso > 0) {
                $entry = CashRegisterEntry::create([
                    'cash_register_id' => $caixa->id,
                    'booking_id' => $booking->id,
                    'type' => 'expense',
                    'amount' => $reembolso,
                    'description' => 'Reembolso cancelamento reserva #' . $booking->numeroNaArena(),
                    'created_by' => $createdBy,
                ]);
                $entryId = $entry->id;
            }

            $pagamento->update([
                'refunded_at' => now(),
                'refund_amount' => $reembolso,
                'refund_cash_register_entry_id' => $entryId,
            ]);
        });

        return $pagamento;
    }

    /**
     * Lança no caixa (aberto) a SAÍDA de um reembolso que ficou pendente (feito
     * com o caixa fechado). Cria a entrada de despesa e vincula ao pagamento.
     */
    public static function lancarReembolso(Payment $pagamento, CashRegister $caixa, ?int $createdBy = null): void
    {
        if ($pagamento->refund_cash_register_entry_id || (float) $pagamento->refund_amount <= 0) {
            return;
        }

        $pagamento->loadMissing('booking');

        DB::transaction(function () use ($pagamento, $caixa, $createdBy) {
            // "Feito por" = quem INICIOU o cancelamento (cliente ou staff). Quem
            // lança agora só transporta a saída pro caixa, não é o autor.
            $autor = $pagamento->booking?->cancelled_by ?? $createdBy;

            $entry = CashRegisterEntry::create([
                'cash_register_id' => $caixa->id,
                'booking_id' => $pagamento->booking_id,
                'type' => 'expense',
                'amount' => $pagamento->refund_amount,
                'description' => 'Reembolso cancelamento reserva #'
                    . (optional($pagamento->booking)->numeroNaArena() ?? $pagamento->booking_id),
                'created_by' => $autor,
            ]);

            $pagamento->update(['refund_cash_register_entry_id' => $entry->id]);
        });
    }

    /**
     * Lança no caixa (aberto) um pagamento que ficou pendente (feito com o
     * caixa fechado). Cria a entrada e vincula ao pagamento.
     */
    public static function lancarNoCaixa(Payment $pagamento, CashRegister $caixa, ?int $createdBy = null): void
    {
        $pagamento->loadMissing('booking.client');

        DB::transaction(function () use ($pagamento, $caixa, $createdBy) {
            // "Feito por" = quem realmente pagou. Só pagamentos ONLINE ficam
            // pendentes de lançamento, e quem pagou online foi o CLIENTE — não o
            // staff que está lançando agora (esse só transporta pro caixa).
            $autor = $pagamento->origin === 'online'
                ? ($pagamento->booking?->client?->user_id ?? $createdBy)
                : $createdBy;

            $entry = CashRegisterEntry::create([
                'cash_register_id' => $caixa->id,
                'booking_id' => $pagamento->booking_id,
                'type' => 'income',
                'amount' => $pagamento->amount,
                'description' => 'Pagamento reserva #' . (optional($pagamento->booking)->numeroNaArena() ?? $pagamento->booking_id)
                    . ' — ' . ($pagamento->paymentMethod->label ?? 'Pagamento'),
                'created_by' => $autor,
            ]);

            $pagamento->update(['cash_register_entry_id' => $entry->id]);
        });
    }
}
