<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arenas', function (Blueprint $table) {
            // O dono cobra taxa de cancelamento de reservas confirmadas?
            $table->boolean('charges_cancellation_fee')->default(false)->after('active');
            // Tipo da taxa: valor fixo (R$) ou porcentagem do valor da reserva.
            $table->enum('cancellation_fee_type', ['fixed', 'percent'])->nullable()->after('charges_cancellation_fee');
            // Valor: R$ se fixo, ou o número do percentual (ex.: 30 = 30%).
            $table->decimal('cancellation_fee_value', 10, 2)->nullable()->after('cancellation_fee_type');
            // Quando a taxa se aplica: sempre (após confirmar) ou só dentro de uma janela.
            $table->enum('cancellation_fee_mode', ['always', 'window'])->nullable()->after('cancellation_fee_value');
            // Janela (horas antes do início) em que passa a ter taxa. Só p/ modo 'window'.
            $table->unsignedInteger('cancellation_fee_window_hours')->nullable()->after('cancellation_fee_mode');
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Taxa efetivamente cobrada ao cancelar (null = cancelada sem taxa / não cancelada).
            $table->decimal('cancellation_fee_amount', 10, 2)->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('cancellation_fee_amount');
        });

        Schema::table('arenas', function (Blueprint $table) {
            $table->dropColumn([
                'charges_cancellation_fee',
                'cancellation_fee_type',
                'cancellation_fee_value',
                'cancellation_fee_mode',
                'cancellation_fee_window_hours',
            ]);
        });
    }
};
