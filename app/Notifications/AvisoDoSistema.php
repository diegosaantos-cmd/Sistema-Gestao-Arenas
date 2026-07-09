<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envia por e-mail o mesmo aviso que aparece no sino do sistema
 * (reserva confirmada, cancelada, reagendada, não paga, mensagem da arena...).
 *
 * Implementa ShouldQueue: com QUEUE_CONNECTION=sync o envio é imediato, mas
 * basta trocar para `database` e subir um worker (`php artisan queue:work`)
 * para o e-mail sair em segundo plano, sem travar a tela de quem confirmou
 * a reserva enquanto o servidor SMTP responde.
 */
class AvisoDoSistema extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $titulo,
        private readonly string $corpo,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->titulo.' — ArenaPlay')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line($this->corpo)
            ->action('Ver no sistema', route('notifications.index'))
            ->line('Este aviso também está disponível no sino de notificações.')
            ->salutation('Equipe ArenaPlay');
    }
}
