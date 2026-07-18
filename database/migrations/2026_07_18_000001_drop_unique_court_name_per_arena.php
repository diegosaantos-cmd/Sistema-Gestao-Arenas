<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove o índice único (arena_id, name) de `courts`.
 *
 * MOTIVO: índice único do banco NÃO enxerga soft delete. Com ele, excluir a
 * "Quadra A" e cadastrar outra "Quadra A" na mesma arena estourava um erro de
 * SQL (1062 Duplicate entry) na cara do usuário — mesmo com a validação da
 * aplicação aprovando, porque ela (corretamente) ignora as quadras excluídas.
 *
 * A unicidade continua garantida na APLICAÇÃO, que já fazia esse trabalho de
 * forma mais completa (ignora espaços/maiúsculas, checa duplicatas dentro do
 * próprio formulário e ignora as quadras excluídas):
 *   - QuadraController::store  (adicionar quadra a uma arena existente)
 *   - QuadraController::update (editar quadra)
 *   - ArenaController::temNomesDeQuadraDuplicados (criação de arena e cadastro
 *     de proprietário)
 *
 * É a MESMA escolha já documentada para o nome da arena, em
 * create_arenas_table: "Sem unique no banco: a unicidade do nome é garantida na
 * aplicação (...) assim um nome de arena excluída pode ser reutilizado".
 */
return new class extends Migration
{
    public function up(): void
    {
        // A FK de arena_id se apoiava no índice único (era o único índice que
        // continha essa coluna). Sem criar um substituto antes, o MySQL recusa
        // removê-lo com "needed in a foreign key constraint".
        Schema::table('courts', function (Blueprint $table) {
            $table->index('arena_id', 'courts_arena_id_index');
        });

        Schema::table('courts', function (Blueprint $table) {
            $table->dropUnique('uq_court_name_per_arena');
        });
    }

    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->unique(['arena_id', 'name'], 'uq_court_name_per_arena');
        });

        Schema::table('courts', function (Blueprint $table) {
            $table->dropIndex('courts_arena_id_index');
        });
    }
};
