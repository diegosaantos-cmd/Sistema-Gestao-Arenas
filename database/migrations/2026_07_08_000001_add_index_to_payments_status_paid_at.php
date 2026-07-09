<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice para acelerar as somas de faturamento
     * (WHERE payments.status = 'paid' AND payments.paid_at BETWEEN ...).
     * Não muda dados nem comportamento — só desempenho.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'paid_at'], 'payments_status_paid_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_paid_at_index');
        });
    }
};
