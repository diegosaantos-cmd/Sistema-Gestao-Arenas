<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();        // destinatário (usuário do cliente)
            $table->unsignedBigInteger('arena_id')->nullable()->index(); // arena remetente
            $table->unsignedBigInteger('sent_by')->nullable();     // usuário (dono/funcionário) que enviou
            $table->string('title');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_notifications');
    }
};
