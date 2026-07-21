<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda no pagamento o texto com que ele deve aparecer no caixa.
 *
 * O PaymentService::registrar() já recebia uma descrição, mas ela só era usada
 * se o caixa estivesse ABERTO na hora — ia direto para o lançamento e se perdia.
 * Com o caixa fechado o pagamento fica "a lançar", e quem lançava depois
 * (lancarNoCaixa) montava o texto do zero, sempre como "Pagamento reserva #N".
 *
 * O efeito no caixa: uma TAXA DE CANCELAMENTO lançada mais tarde aparecia como
 * pagamento da reserva. Quem lia o caixa via a reserva cancelada como paga, e o
 * valor da taxa como se fosse o valor da reserva.
 *
 * Não dá para deduzir a natureza pelo estado da reserva: uma reserva cancelada
 * pode ter tanto a taxa quanto o pagamento original (feito antes, reembolsado
 * depois) esperando lançamento. Só o próprio pagamento sabe o que é — por isso
 * a coluna.
 *
 * Nulo nos pagamentos antigos: quem lança usa o texto padrão quando não houver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('description', 255)
                ->nullable()
                ->after('origin');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
