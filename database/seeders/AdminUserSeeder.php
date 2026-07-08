<?php

namespace Database\Seeders;

use App\Models\SystemAdmin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Cria (ou mantém) um administrador do sistema padrão. Roda toda vez que
     * o banco é semeado, em qualquer máquina. Idempotente: não duplica.
     *
     * Login: adminteste@gmail.com  ·  Senha: 12345678
     */
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::updateOrCreate(
                ['email' => 'adminteste@gmail.com'],
                [
                    'name' => 'admin',
                    'phone' => null,
                    'password_hash' => '12345678',   // o cast "hashed" do model faz o hash
                    'terms_accepted_at' => now(),
                    'active' => true,
                    'type' => 'admin',
                ]
            );

            // Vínculo na tabela de administradores do sistema.
            SystemAdmin::updateOrCreate(['user_id' => $user->id]);
        });
    }
}
