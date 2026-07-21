<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liga a notificação à reserva que a originou.
 *
 * Os avisos ao staff guardavam o nome do cliente escrito no texto ("Fulano
 * pagou R$ ..."). Quando o cliente encerrava a conta, esse nome ficava lá para
 * sempre — a anonimização não tinha como alcançá-lo, porque era só texto solto,
 * sem vínculo de volta ao cliente.
 *
 * Com a reserva referenciada, o texto passa a guardar o marcador {cliente} no
 * lugar do nome, e quem exibe resolve na hora com Booking::nomeCliente() — que
 * já devolve "Cliente excluído" quando a conta saiu. O nome deixa de existir
 * gravado; nasce na exibição, a partir do estado atual.
 *
 * Nulo é o normal: a maioria das notificações não é sobre reserva (e as antigas
 * não têm de onde recuperar o vínculo). nullOnDelete para uma reserva apagada de
 * verdade não derrubar o aviso — embora reserva use soft delete, então isso
 * quase nunca acontece.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->foreignId('booking_id')
                ->nullable()
                ->after('arena_id')
                ->constrained('bookings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });
    }
};
