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
        private readonly ?string $arena = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Mostra qual ARENA gerou o aviso (reserva, mensagem etc.), tanto no
        // assunto quanto numa linha em destaque — assim o cliente sabe a origem.
        $assunto = $this->arena
            ? $this->titulo.' — '.$this->arena.' · ArenaPlay'
            : $this->titulo.' — ArenaPlay';

        $mail = (new MailMessage())
            ->subject($assunto)
            ->greeting('Olá, '.$notifiable->name.'!');

        if ($this->arena) {
            $mail->line('**Arena:** '.$this->arena);
        }

        return $mail
            ->line($this->corpo)
            ->action('Ver no sistema', route('notifications.index'))
            ->line('Este aviso também está disponível no sino de notificações.')
            ->salutation('Equipe ArenaPlay');
    }
}
