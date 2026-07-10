<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove bookings.employee_id.
     *
     * Era uma versão mais estreita e mais antiga do created_by: significava
     * "quem registrou", mas só funcionava para funcionário (FK -> employees) e
     * nunca chegou a ser preenchida (0 linhas). O created_by (FK -> users) cobre
     * o mesmo propósito e ainda representa dono e admin. Manter as duas convidava
     * a preencher a coluna errada.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('client_id')
                ->constrained('employees')
                ->nullOnDelete();
        });
    }
};
