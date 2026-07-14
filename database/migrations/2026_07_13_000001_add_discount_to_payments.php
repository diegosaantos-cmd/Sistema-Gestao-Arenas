<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Desconto aplicado ao receber a reserva no caixa. O `amount` já é o
            // valor LÍQUIDO (total da reserva − desconto); estes campos guardam
            // quanto foi o desconto e o motivo, para aparecer em todos os
            // registros da reserva (nada oculto). 0 = sem desconto.
            $table->decimal('discount_amount', 10, 2)
                ->default(0)
                ->after('amount');
            $table->string('discount_reason', 255)
                ->nullable()
                ->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_reason']);
        });
    }
};
