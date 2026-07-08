<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'admins' era redundante com 'system_admins' (nunca foi usada).
        Schema::dropIfExists('admins');

        // CPF não é necessário no administrador do sistema.
        Schema::table('system_admins', function (Blueprint $table) {
            $table->dropUnique(['cpf']);
            $table->dropColumn('cpf');
        });
    }

    public function down(): void
    {
        Schema::table('system_admins', function (Blueprint $table) {
            $table->string('cpf', 11)->nullable()->unique()->after('user_id');
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
