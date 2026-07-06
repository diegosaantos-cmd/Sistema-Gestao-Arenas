<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Forma de pagamento escolhida pelo cliente na reserva (editável).
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('total_amount')
                ->constrained('payment_methods')
                ->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            // Entrada de caixa gerada por este pagamento. NULL = pago mas ainda
            // NÃO lançado no caixa (feito com o caixa fechado -> "a lançar").
            $table->foreignId('cash_register_entry_id')
                ->nullable()
                ->after('origin')
                ->constrained('cash_register_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['cash_register_entry_id']);
            $table->dropColumn('cash_register_entry_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
    }
};
