<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Coluna exigida pela verificação de e-mail do Laravel. O model já tinha o
     * cast de `email_verified_at`, mas a coluna nunca existiu no banco.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        // Quem já está cadastrado passa a contar como verificado. Sem isto, ao
        // ligar a verificação TODOS os usuários atuais ficariam trancados fora
        // do sistema, esperando um e-mail que nunca receberam.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
