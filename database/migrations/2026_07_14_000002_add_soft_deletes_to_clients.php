<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft delete em clients: quando um cliente vira proprietário usando a
     * própria conta, o registro de cliente é "encerrado" (soft delete), mas o
     * histórico de reservas continua mostrando o nome dele
     * (Booking::client() usa withTrashed).
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
