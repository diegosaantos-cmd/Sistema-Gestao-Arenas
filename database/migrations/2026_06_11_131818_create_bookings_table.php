<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {

            $table->id();

            $table->foreignId('court_id')
                ->constrained('courts')
                ->restrictOnDelete();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->restrictOnDelete();

            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->date('date');

            $table->time('start_time');

            $table->time('end_time');

            $table->decimal('total_amount', 10, 2);

            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled',
                'completed'
            ])->default('pending');

            $table->text('notes')
                ->nullable();

            $table->foreignId('cancelled_by')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            // Sem índice único de horário: a unicidade do slot é garantida na
            // aplicação (checagem que ignora reservas canceladas, permitindo
            // re-agendar um horário cancelado).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};