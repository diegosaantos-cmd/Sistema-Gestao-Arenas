<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Libera o nome da empresa e o CPF/CNPJ de uma empresa EXCLUÍDA para novo uso.
 *
 * Os índices `owners_company_name_unique` e `owners_tax_id_unique` eram únicos
 * comuns, que não enxergam soft delete: uma empresa excluída travaria o CNPJ
 * PARA SEMPRE — nem o próprio dono nem outra pessoa poderiam se cadastrar com
 * ele de novo.
 *
 * Aplica o mesmo padrão já usado em bookings.slot_ativo e em
 * arenas/courts.nome_ativo: coluna gerada que fica NULL quando a linha está
 * excluída (o MySQL aceita vários NULLs num índice único), então o valor volta
 * a ficar livre e os registros ativos seguem protegidos.
 *
 * As regras espelham as da aplicação:
 *   - empresa: ignora espaços e maiúsculas (ArenaController::chaveComparacao),
 *     igual à validação em RegisterArenaOwnerController/Owner\ProfileController;
 *   - documento: comparação exata, porque o tax_id é gravado só com dígitos
 *     (preg_replace('/\D/','')).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE owners
            ADD COLUMN empresa_ativa VARCHAR(170)
            GENERATED ALWAYS AS (
                CASE WHEN deleted_at IS NULL
                     THEN REPLACE(LOWER(company_name), ' ', '')
                END
            ) STORED
        ");

        DB::statement("
            ALTER TABLE owners
            ADD COLUMN documento_ativo VARCHAR(20)
            GENERATED ALWAYS AS (
                CASE WHEN deleted_at IS NULL THEN tax_id END
            ) STORED
        ");

        DB::statement('ALTER TABLE owners ADD UNIQUE INDEX owners_empresa_ativa_unique (empresa_ativa)');
        DB::statement('ALTER TABLE owners ADD UNIQUE INDEX owners_documento_ativo_unique (documento_ativo)');

        // Só agora remove os antigos: as colunas geradas já protegem os ativos.
        DB::statement('ALTER TABLE owners DROP INDEX owners_company_name_unique');
        DB::statement('ALTER TABLE owners DROP INDEX owners_tax_id_unique');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE owners ADD UNIQUE INDEX owners_company_name_unique (company_name)');
        DB::statement('ALTER TABLE owners ADD UNIQUE INDEX owners_tax_id_unique (tax_id)');

        DB::statement('ALTER TABLE owners DROP INDEX owners_empresa_ativa_unique');
        DB::statement('ALTER TABLE owners DROP INDEX owners_documento_ativo_unique');
        DB::statement('ALTER TABLE owners DROP COLUMN empresa_ativa');
        DB::statement('ALTER TABLE owners DROP COLUMN documento_ativo');
    }
};
