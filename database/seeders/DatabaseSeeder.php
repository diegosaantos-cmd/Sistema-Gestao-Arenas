<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PaymentMethodSeeder::class,
            AdminUserSeeder::class,
        ]);

        // TODO: criar usuário de teste compatível com a tabela users
        // (usa password_hash e type). A UserFactory padrão do Jetstream
        // quebra porque insere colunas inexistentes (email_verified_at, etc.).
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
