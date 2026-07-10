<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reserva feita presencialmente (no balcão da arena) pelo dono, gerente ou
     * atendente, para um cliente que não tem cadastro no site.
     *
     * Decisões de modelagem:
     *
     * - `client_id` passa a ser OPCIONAL. Ele continua significando "o cliente
     *   cadastrado" — fica nulo quando a pessoa não tem conta. Não o usamos para
     *   guardar quem registrou: a coluna teria dois significados, e dono/atendente
     *   nem sequer têm registro em `clients`.
     *
     * - `created_by` guarda QUEM registrou (dono, gerente ou atendente). É a
     *   informação de controle que faltava, e já serve às telas de gerente e
     *   atendente que ainda serão criadas.
     *
     * - Os dados do responsável presencial ficam na própria reserva (não em tabela
     *   separada): cada reserva tem no máximo um responsável, e o dado é um retrato
     *   do momento (o telefone que ele deu naquele dia).
     */
    public function up(): void
    {
        // MODIFY direto: o Laravel não altera coluna que participa de foreign key
        // sem antes removê-la. A FK (client_id -> clients) continua válida.
        DB::statement('ALTER TABLE bookings MODIFY client_id BIGINT UNSIGNED NULL');

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('guest_name', 120)->nullable()->after('client_id');
            $table->string('guest_phone', 20)->nullable()->after('guest_name');
            $table->string('guest_email', 150)->nullable()->after('guest_phone');

            $table->foreignId('created_by')
                ->nullable()
                ->after('employee_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('origin', ['site', 'presencial'])
                ->default('site')
                ->after('created_by');

            $table->index(['origin']);
        });

        // Tudo o que existe hoje veio do site.
        DB::table('bookings')->update(['origin' => 'site']);

        // O banco garante que a reserva sempre tem um responsável identificável:
        // ou um cliente cadastrado, ou o nome de quem foi até a arena.
        DB::statement('
            ALTER TABLE bookings
            ADD CONSTRAINT chk_bookings_cliente_ou_convidado
            CHECK (client_id IS NOT NULL OR guest_name IS NOT NULL)
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bookings DROP CHECK chk_bookings_cliente_ou_convidado');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['origin']);
            $table->dropColumn(['guest_name', 'guest_phone', 'guest_email', 'created_by', 'origin']);
        });

        DB::statement('ALTER TABLE bookings MODIFY client_id BIGINT UNSIGNED NOT NULL');
    }
};
