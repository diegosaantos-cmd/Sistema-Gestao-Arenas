<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notificações servem para qualquer usuário (cliente, dono, funcionário,
     * admin) — todos são "users". Uma tabela genérica basta.
     * Nome user_notifications (não notifications) para não colidir com a
     * tabela reservada do sistema de notificações do Laravel (trait Notifiable).
     */
    public function up(): void
    {
        Schema::rename('client_notifications', 'user_notifications');
    }

    public function down(): void
    {
        Schema::rename('user_notifications', 'client_notifications');
    }
};
