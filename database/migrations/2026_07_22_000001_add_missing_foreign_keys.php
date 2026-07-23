<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha as chaves estrangeiras que faltavam.
 *
 * Cinco colunas apontavam para outra tabela só por convenção (tinham o
 * `user_id`/`arena_id`/`*_by` e a relação no model), mas SEM constraint no
 * banco. O efeito: o banco não garantia integridade referencial e o DER —
 * gerado a partir das FKs reais — desenhava `feedbacks` e `user_notifications`
 * soltas, sem ligação com usuário/arena.
 *
 * Regras de ON DELETE seguindo a convenção já usada no projeto:
 *   - dono (user_id, NOT NULL) → RESTRICT: não se apaga usuário com registro.
 *   - quem agiu (*_by, nullable) → SET NULL: mantém o registro, solta o autor.
 *   - referência de arena (nullable) → SET NULL: a notificação sobrevive à
 *     arena. (Na prática nada dispara: usuários e arenas usam soft delete.)
 *
 * Conferido antes: zero valores órfãos, então nenhuma constraint falha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('sent_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('arena_id')->references('id')->on('arenas')->nullOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['sent_by']);
            $table->dropForeign(['arena_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
        });
    }
};
