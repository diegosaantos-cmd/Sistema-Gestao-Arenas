<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rede de segurança no BANCO contra reserva duplicada: nenhuma quadra pode ter
     * duas reservas ATIVAS (pendente/confirmada) no mesmo dia e mesmo horário.
     *
     * Um índice único direto em (court_id, date, start_time) impediria re-reservar
     * um horário depois de cancelado. Por isso usamos uma coluna gerada que fica
     * NULL quando a reserva não está ativa — o MySQL aceita vários NULLs num índice
     * único, então horários cancelados/realizados voltam a ficar livres.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE bookings
            ADD COLUMN slot_ativo VARCHAR(64)
            GENERATED ALWAYS AS (
                CASE WHEN status IN ('pending','confirmed')
                     THEN CONCAT(court_id, '|', date, '|', start_time)
                END
            ) STORED
        ");

        DB::statement('ALTER TABLE bookings ADD UNIQUE INDEX bookings_slot_ativo_unique (slot_ativo)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bookings DROP INDEX bookings_slot_ativo_unique');
        DB::statement('ALTER TABLE bookings DROP COLUMN slot_ativo');
    }
};
