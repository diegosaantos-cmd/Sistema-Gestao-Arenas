<?php

use App\Models\Booking;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Atualiza os status das reservas sem depender de alguém abrir uma tela:
 * confirma as pendentes cujo prazo expirou e marca como realizadas as
 * confirmadas cujo horário já terminou (notificando quem não pagou).
 *
 * As telas ainda chamam essas rotinas como rede de segurança, para o sistema
 * funcionar mesmo em ambiente sem agendador (ex.: WAMP local sem cron).
 * Em produção, agende o scheduler do Laravel (cron a cada minuto):
 *     * * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
 */
Artisan::command('bookings:sync-status', function () {
    Booking::autoConfirmarExpiradas();
    Booking::autoCompletarRealizadas();

    $this->info('Status das reservas atualizados.');
})->purpose('Confirma reservas com prazo expirado e conclui as já realizadas');

Schedule::command('bookings:sync-status')
    ->everyFiveMinutes()
    ->withoutOverlapping();
