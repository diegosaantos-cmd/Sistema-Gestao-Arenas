<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::updateOrCreate(
                ['email' => 'admin@gmail.com'],
                [
                    'name' => 'Administrador Geral',
                    'phone' => null,
                    'password_hash' => '12345678',
                    'type' => 'admin',
                    'active' => true,
                ]
            );

            DB::table('admins')->updateOrInsert(
                ['user_id' => $user->id],
                ['created_by' => null, 'created_at' => now()]
            );
        });
    }
}
