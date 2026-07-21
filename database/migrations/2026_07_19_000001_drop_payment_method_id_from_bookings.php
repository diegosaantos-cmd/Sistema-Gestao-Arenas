<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove `bookings.payment_method_id`.
 *
 * A coluna nasceu para guardar a forma de pagamento escolhida no AGENDAMENTO,
 * mas o fluxo mudou: hoje o cliente escolhe a forma na hora de pagar, e quem
 * registra isso é a tabela `payments` — que tem a própria payment_method_id e
 * alimenta o caixa, o faturamento e todas as telas que exibem "Forma:".
 *
 * A criação da reserva nunca preenchia esta coluna. Ela só era escrita na tela
 * de pagamento e lida num único ponto (pré-selecionar o select daquela mesma
 * tela), o que não justifica mantê-la — ainda mais tendo nome idêntico ao da
 * coluna de `payments`, com significado diferente: a de lá é o pagamento que
 * ocorreu, a daqui era só a intenção. Divergiam quando o cliente marcava PIX e
 * acabava pagando em dinheiro no balcão.
 *
 * Removidos junto (dependiam dela): a relação Booking::paymentMethod() e o
 * método Booking::podePagarOnline(), que não era chamado em lugar nenhum.
 *
 * Nada de contabilidade se perde: o histórico de pagamentos está em `payments`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign('bookings_payment_method_id_foreign');
            $table->dropColumn('payment_method_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('total_amount')
                ->constrained('payment_methods')
                ->nullOnDelete();
        });
    }
};
