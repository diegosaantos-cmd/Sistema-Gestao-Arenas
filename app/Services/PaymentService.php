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

        $descricao = $descricao ?? self::descricaoPadrao($booking, $metodo);

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
                // Guardado mesmo com o caixa aberto: se o pagamento ficar para
                // lançar depois, é daqui que sai o texto — e é o que impedia a
                // taxa de cancelamento de virar "Pagamento reserva" no caixa.
                'description' => $descricao,
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
                    'description' => self::descricaoReembolso($booking),
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

        // $createdBy (quem está lançando) não entra no autor de propósito: ver
        // o comentário abaixo.
        DB::transaction(function () use ($pagamento, $caixa) {
            // "Feito por" = quem INICIOU o cancelamento (cliente ou staff). Quem
            // lança agora só transporta a saída pro caixa, não é o autor — por
            // isso, sem essa informação, o autor fica nulo em vez de cair nele.
            $autor = $pagamento->booking?->cancelled_by;

            $entry = CashRegisterEntry::create([
                'cash_register_id' => $caixa->id,
                'booking_id' => $pagamento->booking_id,
                'type' => 'expense',
                'amount' => $pagamento->refund_amount,
                'description' => self::descricaoReembolso($pagamento->booking, $pagamento->booking_id),
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
            //
            // Cliente com a conta excluída fica NULO, e não "quem está lançando":
            // cair no staff creditava a ele um pagamento que não fez. Sem
            // cliente, o certo é não haver autor — a tela mostra que o pagamento
            // foi do cliente removido, lendo isso da própria reserva.
            $autor = $pagamento->origin === 'online'
                ? $pagamento->booking?->client?->user_id
                : $createdBy;

            $entry = CashRegisterEntry::create([
                'cash_register_id' => $caixa->id,
                'booking_id' => $pagamento->booking_id,
                'type' => 'income',
                'amount' => $pagamento->amount,
                // O texto vem do próprio pagamento, gravado quando ele nasceu.
                // Montá-lo aqui do zero era o que transformava taxa de
                // cancelamento em "Pagamento reserva": daqui não dá para saber
                // a natureza do pagamento, só ele sabe. O padrão cobre os
                // pagamentos criados antes desta coluna existir.
                'description' => $pagamento->description
                    ?: self::descricaoPadrao($pagamento->booking, $pagamento->paymentMethod, $pagamento->booking_id),
                'created_by' => $autor,
            ]);

            $pagamento->update(['cash_register_entry_id' => $entry->id]);
        });
    }

    /**
     * Texto padrão de um pagamento no caixa.
     *
     * Só entra em cena quando o pagamento não traz o próprio texto — casos
     * criados antes da coluna `description` existir. Pagamento novo sempre
     * carrega o seu, porque só ele sabe se é reserva ou taxa.
     */
    private static function descricaoPadrao(?Booking $booking, ?PaymentMethod $metodo, ?int $bookingId = null): string
    {
        $numero = $booking?->numeroNaArena() ?? $bookingId;

        return 'Pagamento reserva #' . $numero . ' — ' . ($metodo->label ?? 'Pagamento');
    }

    /**
     * Texto da SAÍDA de reembolso no caixa.
     *
     * Quando fica taxa retida, ela aparece no próprio texto. Sem isso, o caixa
     * mostrava a entrada cheia da reserva e uma saída menor, e descobrir quanto
     * a arena ficou de taxa exigia subtrair uma da outra na mão.
     */
    private static function descricaoReembolso(?Booking $booking, ?int $bookingId = null): string
    {
        $numero = $booking?->numeroNaArena() ?? $bookingId;
        $taxa = (float) ($booking->cancellation_fee_amount ?? 0);

        return 'Reembolso cancelamento reserva #' . $numero
            . ($taxa > 0 ? ' (taxa retida R$ ' . number_format($taxa, 2, ',', '.') . ')' : '');
    }
}
