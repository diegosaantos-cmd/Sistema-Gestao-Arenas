<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Arena em que o funcionário trabalha.
            $table->foreignId('arena_id')
                ->constrained('arenas')
                ->restrictOnDelete();

            $table->string('position', 80);

            $table->enum('access_level', [
                'basic',
                'managerial'
            ])->default('basic');

            $table->boolean('active')
                ->default(true);

            $table->timestamp('created_at')
                ->useCurrent();

            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
