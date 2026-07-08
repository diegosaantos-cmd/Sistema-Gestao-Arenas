<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sugestões e reportes de bug enviados por usuários (clientes, donos,
     * funcionários) ao administrador do sistema. Separado das notificações.
     */
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();     // quem enviou
            $table->enum('tipo', ['sugestao', 'bug'])->default('sugestao');
            $table->string('assunto');
            $table->text('mensagem');
            $table->enum('status', ['aberto', 'em_andamento', 'resolvido'])->default('aberto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
