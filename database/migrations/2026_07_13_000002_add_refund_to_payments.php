<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Estorno/reembolso do pagamento quando a reserva JÁ PAGA é cancelada.
            // Devolve (amount − taxa), ou tudo se não houver taxa. Espelha o fluxo
            // do pagamento: a SAÍDA no caixa é lançada na hora se o caixa estiver
            // aberto, senão fica pendente (refund_cash_register_entry_id = null) e
            // é lançada quando o caixa abrir.
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->decimal('refund_amount', 10, 2)->default(0)->after('refunded_at');
            $table->foreignId('refund_cash_register_entry_id')
                ->nullable()
                ->after('refund_amount')
                ->constrained('cash_register_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['refund_cash_register_entry_id']);
            $table->dropColumn(['refunded_at', 'refund_amount', 'refund_cash_register_entry_id']);
        });
    }
};
