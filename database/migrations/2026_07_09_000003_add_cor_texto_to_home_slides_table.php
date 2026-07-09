<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cor do título/subtítulo de cada slide.
     *
     * 'claro'  = texto branco sobre faixa escura (foto escura)
     * 'escuro' = texto escuro sobre faixa clara  (foto clara)
     *
     * A faixa semitransparente atrás do texto é aplicada automaticamente conforme
     * a cor escolhida: sem ela, trocar a foto por uma de brilho parecido faria o
     * texto sumir de novo.
     */
    public function up(): void
    {
        Schema::table('home_slides', function (Blueprint $table) {
            $table->enum('cor_texto', ['claro', 'escuro'])
                ->default('claro')
                ->after('subtitulo');
        });
    }

    public function down(): void
    {
        Schema::table('home_slides', function (Blueprint $table) {
            $table->dropColumn('cor_texto');
        });
    }
};
