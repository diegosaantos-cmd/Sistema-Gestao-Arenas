<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca quando o admin já viu o feedback. O contador (badge) de
     * "não lidos" no painel conta os feedbacks com read_at NULL; ao abrir
     * a tela de Sugestões e bugs, todos são marcados como lidos e o badge zera.
     */
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};
