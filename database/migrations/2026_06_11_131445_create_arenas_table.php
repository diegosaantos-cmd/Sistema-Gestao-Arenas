<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arenas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('owner_id')
                ->constrained('owners')
                ->restrictOnDelete();

            // Sem unique no banco: a unicidade do nome é garantida na aplicação
            // (ignorando espaços/maiúsculas e as arenas excluídas via soft delete),
            // assim um nome de arena excluída pode ser reutilizado.
            $table->string('name', 120);

            $table->text('description')
                ->nullable();

            $table->string('address_rua', 120);

            $table->string('address_bairro', 100);

            $table->string('address_numero', 15);

            $table->string('phone', 20)
                ->nullable();

            $table->string('contact_email', 150)
                ->nullable();

            $table->boolean('active')
                ->default(true);

            $table->timestamp('created_at')
                ->useCurrent();

             $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            // Exclusão lógica: mantém o histórico (reservas, etc.) no banco.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arenas');
    }
};
