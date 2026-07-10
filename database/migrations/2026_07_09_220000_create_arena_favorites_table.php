<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arenas favoritadas pelo cliente.
     *
     * Tabela de ligação (muitos-para-muitos): um cliente favorita várias arenas
     * e uma arena é favoritada por vários clientes. Guardar uma lista numa coluna
     * de `clients` perderia a integridade referencial, a proteção contra duplicata
     * e tornaria a consulta "minhas favoritas" um LIKE sem índice.
     *
     * Aponta para `clients` (e não `users`) por consistência com `bookings`.
     */
    public function up(): void
    {
        Schema::create('arena_favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->foreignId('arena_id')
                ->constrained('arenas')
                ->cascadeOnDelete();

            // Ordenar pelas favoritadas mais recentes.
            $table->timestamp('created_at')->useCurrent();

            // O banco impede favoritar a mesma arena duas vezes (ex.: duplo clique).
            $table->unique(['client_id', 'arena_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arena_favorites');
    }
};
