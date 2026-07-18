<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rede de segurança no BANCO contra nome duplicado de arena e de quadra.
 *
 * A validação da aplicação já cobre o uso normal, mas ela não protege contra
 * duas requisições simultâneas (duplo clique): as duas passam na checagem antes
 * de qualquer uma gravar. Faltava a garantia do banco.
 *
 * Um índice único direto em `name` não serve, por dois motivos:
 *   1. não enxerga soft delete — travaria para sempre o nome de um registro
 *      excluído (foi exatamente por isso que removemos uq_court_name_per_arena);
 *   2. não aplicaria a regra real, que ignora espaços e maiúsculas
 *      (ArenaController::chaveComparacao) — "QuadraA" e "Quadra A" passariam.
 *
 * A solução é a MESMA já usada em bookings (add_unique_active_slot_to_bookings):
 * uma coluna gerada que fica NULL quando a linha está excluída. O MySQL aceita
 * vários NULLs num índice único, então nomes de registros excluídos ficam livres
 * para reuso, e os ativos ficam protegidos — com a regra exata da aplicação.
 *
 * Escopos (iguais aos da validação):
 *   - arena: nome único GLOBAL (é como o cliente identifica o lugar);
 *   - quadra: nome único DENTRO da arena (por isso o arena_id no CONCAT).
 *
 * O nome salvo já passa por normalizarTexto(), que colapsa qualquer espaço em
 * branco num único espaço — então REPLACE(..., ' ', '') aqui equivale ao
 * preg_replace('/\s+/u', '') do PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE arenas
            ADD COLUMN nome_ativo VARCHAR(140)
            GENERATED ALWAYS AS (
                CASE WHEN deleted_at IS NULL
                     THEN REPLACE(LOWER(name), ' ', '')
                END
            ) STORED
        ");

        DB::statement('ALTER TABLE arenas ADD UNIQUE INDEX arenas_nome_ativo_unique (nome_ativo)');

        DB::statement("
            ALTER TABLE courts
            ADD COLUMN nome_ativo VARCHAR(180)
            GENERATED ALWAYS AS (
                CASE WHEN deleted_at IS NULL
                     THEN CONCAT(arena_id, '|', REPLACE(LOWER(name), ' ', ''))
                END
            ) STORED
        ");

        DB::statement('ALTER TABLE courts ADD UNIQUE INDEX courts_nome_ativo_unique (nome_ativo)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE courts DROP INDEX courts_nome_ativo_unique');
        DB::statement('ALTER TABLE courts DROP COLUMN nome_ativo');
        DB::statement('ALTER TABLE arenas DROP INDEX arenas_nome_ativo_unique');
        DB::statement('ALTER TABLE arenas DROP COLUMN nome_ativo');
    }
};
