<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fotos e textos do carrossel da tela inicial, editáveis pelo admin.
     *
     * Tabela própria (e não a court_arena_photos) porque estes slides pertencem
     * ao SITE, não a uma arena ou quadra — guardá-los lá exigiria linhas com
     * arena_id e court_id nulos, usando a ausência de dado como se fosse um tipo.
     */
    public function up(): void
    {
        Schema::create('home_slides', function (Blueprint $table) {
            $table->id();
            $table->string('image_path', 500);
            $table->string('titulo', 120)->nullable();
            $table->string('subtitulo', 255)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            // A home lista sempre por (active, ordem).
            $table->index(['active', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_slides');
    }
};
