<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Troca o enum('claro','escuro') por uma cor hexadecimal livre (#RRGGBB),
     * para o admin escolher qualquer cor no seletor de cores do navegador.
     *
     * A faixa de contraste atrás do texto deixa de depender do enum: passa a ser
     * calculada pela luminosidade da cor escolhida (ver HomeSlide::fundoLegenda).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE home_slides MODIFY cor_texto VARCHAR(7) NOT NULL DEFAULT '#FFFFFF'");

        // Converte os valores antigos, se houver.
        DB::table('home_slides')->where('cor_texto', 'claro')->update(['cor_texto' => '#FFFFFF']);
        DB::table('home_slides')->where('cor_texto', 'escuro')->update(['cor_texto' => '#0B1B2B']);
    }

    public function down(): void
    {
        // Qualquer cor clara volta para 'claro'; o resto para 'escuro'.
        DB::table('home_slides')->where('cor_texto', '#FFFFFF')->update(['cor_texto' => 'claro']);
        DB::table('home_slides')->where('cor_texto', '!=', 'claro')->update(['cor_texto' => 'escuro']);

        DB::statement("ALTER TABLE home_slides MODIFY cor_texto ENUM('claro','escuro') NOT NULL DEFAULT 'claro'");
    }
};
