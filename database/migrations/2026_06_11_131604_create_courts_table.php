<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('arena_id')
                ->constrained('arenas')
                ->restrictOnDelete();

            $table->string('name', 80);

            $table->text('description')
                ->nullable();

            $table->decimal('hourly_rate', 10, 2);

            $table->boolean('active')
                ->default(true);

            $table->timestamp('created_at')
                ->useCurrent();

            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            // Nome da quadra é único dentro da mesma arena (pode repetir entre arenas).
            $table->unique(['arena_id', 'name'], 'uq_court_name_per_arena');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};