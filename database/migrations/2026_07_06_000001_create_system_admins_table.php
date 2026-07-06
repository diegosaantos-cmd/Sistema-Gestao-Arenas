<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('cpf', 11)->nullable()->unique();
            $table->timestamps();
        });

        $agora = now();
        $administradores = DB::table('users')
            ->where('type', 'admin')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($userId) => [
                'user_id' => $userId,
                'cpf' => null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ])
            ->all();

        if ($administradores !== []) {
            DB::table('system_admins')->insert($administradores);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_admins');
    }
};
