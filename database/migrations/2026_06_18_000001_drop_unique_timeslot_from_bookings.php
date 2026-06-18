<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A unicidade do horário é garantida na aplicação (checagem que ignora
        // reservas canceladas). A constraint do banco não distingue status e
        // impedia re-agendar um horário cancelado, então é removida.
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('uq_booking_timeslot');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unique(
                ['court_id', 'date', 'start_time', 'end_time'],
                'uq_booking_timeslot'
            );
        });
    }
};
