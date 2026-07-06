<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_register_entries', function (Blueprint $table) {
            // Quem registrou o lançamento (dono/atendente, ou o cliente no
            // pagamento online). NULL para lançamentos antigos.
            $table->foreignId('created_by')
                ->nullable()
                ->after('description')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_register_entries', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
