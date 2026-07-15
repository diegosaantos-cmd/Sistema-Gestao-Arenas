<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arena_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arena_id')->constrained('arenas')->cascadeOnDelete();
            // Caminho relativo no disco 'public' (ex.: arenas/xxxx.jpg). O arquivo
            // fica fora do Git (storage), então a existência é checada em runtime.
            $table->string('image_path');
            // Ordem no carrossel (a menor é a capa). Definida pelo dono/gerente.
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['arena_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arena_photos');
    }
};
